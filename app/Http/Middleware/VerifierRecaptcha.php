<?php

namespace App\Http\Middleware;

use App\Rules\Recaptcha;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses a public form (sign-in, registration, password reset, contact…)
 * whose "recaptcha" field Google does not confirm, before the controller
 * runs. The error comes back on the "recaptcha" field, shown under the
 * widget.
 */
class VerifierRecaptcha
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate(['recaptcha' => [new Recaptcha]]);

        return $next($request);
    }
}
