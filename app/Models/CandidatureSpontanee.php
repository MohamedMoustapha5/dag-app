<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * An unsolicited job application submitted from the public Formations
 * page — anonymous, no account required, open to any job seeker already
 * trained (whether or not by DAG) and looking for an opportunity. DAG
 * receives the dossier (CV, contact, message) and applies to offres on
 * the candidate's behalf, which turns it into an external apprenant
 * dossier (see versApprenant()).
 */
#[Fillable(['nom', 'email', 'telephone', 'portfolio', 'message', 'cv_path', 'statut', 'apprenant_id'])]
class CandidatureSpontanee extends Model
{
    use HasUuids;

    protected $table = 'candidatures_spontanees';

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /**
     * Returns the apprenant dossier DAG applies from. If the candidate
     * already has an apprenant account (same e-mail), that account is
     * reused; otherwise an external dossier is created — a login-less
     * account (random password, claimable through "mot de passe oublié")
     * whose CV is the one the candidate sent to DAG, shared with
     * entreprises since the dossier was sent for exactly that purpose.
     *
     * @throws ValidationException
     */
    public function versApprenant(): Apprenant
    {
        if ($this->apprenant_id !== null) {
            return Apprenant::where('id', $this->apprenant_id)->firstOrFail();
        }

        $user = User::where('email', $this->email)->first();

        if ($user !== null) {
            $existant = Apprenant::where('user_id', $user->id)->first();

            if ($existant === null) {
                throw ValidationException::withMessages([
                    'candidature_spontanee_id' => "L'adresse {$this->email} appartient déjà à un compte qui n'est pas un apprenant.",
                ]);
            }

            $this->update(['statut' => 'TRAITEE']);

            return $existant;
        }

        $user = User::create([
            'name' => $this->nom,
            'email' => $this->email,
            'password' => Str::random(40),
            'type' => UserType::Apprenant,
        ]);

        $apprenant = Apprenant::create([
            'user_id' => $user->id,
            'telephone' => $this->telephone,
            'cv_path' => $this->cv_path,
            'consentement_cv' => true,
        ]);

        $this->update(['apprenant_id' => $apprenant->id, 'statut' => 'TRAITEE']);

        return $apprenant;
    }
}
