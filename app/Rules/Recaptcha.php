<?php

namespace App\Rules;

use App\Support\Recaptcha as VerificationRecaptcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * The reCAPTCHA token of a public form must be confirmed by Google. Implicit,
 * so a form submitted without the token fails too; always passes while
 * reCAPTCHA is off (no keys configured).
 */
class Recaptcha implements ValidationRule
{
    public bool $implicit = true;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! VerificationRecaptcha::actif()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Cochez la case « Je ne suis pas un robot ».');

            return;
        }

        if (! VerificationRecaptcha::verifier($value, request()->ip())) {
            $fail('La vérification « Je ne suis pas un robot » a expiré ou échoué : cochez de nouveau la case.');
        }
    }
}
