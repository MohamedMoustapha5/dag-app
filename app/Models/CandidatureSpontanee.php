<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An unsolicited job application submitted from the public Formations
 * page — anonymous, no account required, open to any job seeker already
 * trained (whether or not by DAG) and looking for an opportunity.
 */
#[Fillable(['nom', 'email', 'telephone', 'portfolio', 'message', 'cv_path', 'statut'])]
class CandidatureSpontanee extends Model
{
    use HasUuids;

    protected $table = 'candidatures_spontanees';
}
