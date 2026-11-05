<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasskeyLoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\Intention;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureUnthrottledFortifyRoutes();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Reject accounts that are not allowed to log in. Team members sign in
     * on their own guard (AdminAuthController); here, a client account DAG
     * has deactivated is refused.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if ($user->client?->statut === 'INACTIF') {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'teamInvitation' => $this->teamInvitation($request),
            'intention' => Intention::depuis($request->session()->get('url.intended')),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn (Request $request) => Inertia::render('auth/register', [
            'teamInvitation' => $this->teamInvitation($request),
            'intention' => Intention::depuis($request->session()->get('url.intended')),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Per email+IP against guessing one account's password, and per IP alone
        // so cycling through many emails (credential stuffing) is slowed too.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($throttleKey),
                Limit::perMinute(30)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

            return Limit::perMinutes(60, 5)->by($throttleKey)->response(
                fn (Request $request, array $headers) => back()->withErrors([
                    'email' => 'Trop de tentatives. Merci de réessayer dans 1 heure.',
                ])->withHeaders($headers)
            );
        });
    }

    /**
     * Fortify registers the password-reset-request route ("password.email")
     * and the registration route ("register.store") without a throttle slot
     * in config/fortify.php, unlike login/two-factor/passkeys. Web routes
     * load lazily (after every provider's boot() has already run), so attach
     * the limiter when the route is matched instead, which is guaranteed to
     * run before its middleware pipeline is built.
     */
    private function configureUnthrottledFortifyRoutes(): void
    {
        Route::matched(function (RouteMatched $event): void {
            match ($event->route->getName()) {
                'password.email' => $event->route->middleware('throttle:password-reset'),
                'register.store' => $event->route->middleware('throttle:register'),
                default => null,
            };
        });
    }

    /**
     * Get the pending team invitation context for auth pages.
     *
     * @return array{code: string, teamName: string}|null
     */
    private function teamInvitation(Request $request): ?array
    {
        $invitationCode = $request->query('invitation');

        if (! is_string($invitationCode)) {
            return null;
        }

        $invitation = TeamInvitation::query()
            ->with('team')
            ->where('code', $invitationCode)
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->first();

        if (! $invitation) {
            return null;
        }

        return [
            'code' => $invitation->code,
            'teamName' => $invitation->team->name,
        ];
    }
}
