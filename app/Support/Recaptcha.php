<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA v2 ("Je ne suis pas un robot") guarding the public forms
 * (VerifierRecaptcha). It is off until both keys are set, so local
 * development and the test suite run without it.
 *
 * The token the browser sends is only trusted once Google's siteverify
 * endpoint confirms it; a token is single-use and expires after two minutes.
 */
class Recaptcha
{
    private const URL_VERIFICATION = 'https://www.google.com/recaptcha/api/siteverify';

    public static function actif(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key'));
    }

    /** The public key the browser renders the widget with, null when reCAPTCHA is off. */
    public static function cleDuSite(): ?string
    {
        return self::actif() ? (string) config('services.recaptcha.site_key') : null;
    }

    /**
     * Whether Google confirms the token was produced by a person on one of
     * the site's domains.
     *
     * Google being unreachable does not lock everyone out of the site (the
     * rate limiters still apply): the token is let through and the incident
     * logged. A secret key Google rejects is a configuration error: it is
     * logged as such and the token is refused.
     */
    public static function verifier(string $jeton, ?string $ip): bool
    {
        try {
            $reponse = Http::asForm()
                ->timeout((int) config('services.recaptcha.timeout', 5))
                ->post(self::URL_VERIFICATION, array_filter([
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $jeton,
                    'remoteip' => $ip,
                ]));
        } catch (ConnectionException $erreur) {
            Log::warning('reCAPTCHA injoignable : formulaire accepté sans vérification.', ['erreur' => $erreur->getMessage()]);

            return true;
        }

        if ($reponse->serverError()) {
            Log::warning('reCAPTCHA indisponible : formulaire accepté sans vérification.', ['statut' => $reponse->status()]);

            return true;
        }

        $codes = (array) $reponse->json('error-codes', []);

        if (array_intersect($codes, ['missing-input-secret', 'invalid-input-secret']) !== []) {
            Log::error('reCAPTCHA : clé secrète refusée par Google, vérifiez RECAPTCHA_SECRET_KEY.', ['codes' => $codes]);

            return false;
        }

        return $reponse->json('success') === true;
    }
}
