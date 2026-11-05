<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Many tests act as several accounts one after another, while a test
     * keeps a single session. In real use each account has its own session:
     * drop the password fingerprint the `auth.session` middleware keeps, so
     * the next account isn't signed out as if its password had changed.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($this->app->bound('session.store')) {
            $this->app['session.store']->forget(['password_hash_web', 'password_hash_admin']);
        }

        return parent::actingAs($user, $guard);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
