<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-defined override of the default access level a role has on a
 * data domain. Read through App\Support\DroitsMatrix, never directly.
 */
#[Fillable(['role', 'donnee', 'niveau'])]
class DroitRole extends Model
{
    protected $table = 'droits_roles';
}
