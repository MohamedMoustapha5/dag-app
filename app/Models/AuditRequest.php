<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An anonymous "audit de page gratuit" lead captured from the public
 * catalogue (section 6.1 of the cahier des charges) — no account required.
 */
#[Fillable(['page_url', 'email', 'statut'])]
class AuditRequest extends Model
{
    use HasUuids;
}
