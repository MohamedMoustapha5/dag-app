<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request. Changing the email
     * asks for the current password, so a stolen session alone can't move
     * the account to another inbox.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user('web');

        return [
            ...$this->profileRules($user->id),
            'current_password' => [
                Rule::requiredIf(fn () => Str::lower((string) $this->input('email')) !== Str::lower($user->email)),
                'nullable', 'string', 'current_password:web',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Saisissez votre mot de passe actuel pour changer d\'adresse e-mail.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ];
    }
}
