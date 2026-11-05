<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Support\SuppressionCompte;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user('web') instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user('web');

        $user->fill($request->safe()->only(['name', 'email']));

        $emailChange = $user->isDirty('email');

        if ($emailChange) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChange) {
            $user->sendEmailVerificationNotification();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user('web');

        Auth::logout();

        SuppressionCompte::fermer($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
