<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserType;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * The account types a visitor may self-register as. Entreprise
     * partenaire and membre de l'équipe accounts are created by DAG itself
     * (sections 6.6 and 6.9 of the cahier des charges), never through the
     * public registration form.
     *
     * @var array<string>
     */
    private const COMPTE_TYPES = ['particulier', 'entreprise', 'organisation', 'apprenant'];

    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'compte_type' => ['required', 'string', Rule::in(self::COMPTE_TYPES)],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $compteType = $input['compte_type'];
            $estApprenant = $compteType === 'apprenant';

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'type' => $estApprenant ? UserType::Apprenant : UserType::Client,
            ]);

            if ($estApprenant) {
                Apprenant::create(['user_id' => $user->id]);
            } else {
                Client::create([
                    'user_id' => $user->id,
                    'type' => strtoupper($compteType),
                    'nom' => $input['name'],
                ]);
            }

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }
}
