<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * When the password of an account changes (settings, reset, administration),
 * its "remember me" token is renewed: a remember cookie kept on another
 * device no longer signs anyone in. Open sessions are signed out on their
 * next request by AuthenticateSession, which runs on every web request.
 */
trait RevoqueSessionsAuChangementDeMotDePasse
{
    public static function bootRevoqueSessionsAuChangementDeMotDePasse(): void
    {
        static::updating(function (Model $compte): void {
            if ($compte->isDirty('password')) {
                $compte->setAttribute('remember_token', Str::random(60));
            }
        });
    }
}
