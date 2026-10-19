<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A message submitted from the public contact form — anonymous, no
 * account required (section 6.1 of the cahier des charges).
 */
#[Fillable(['nom', 'email', 'sujet', 'message', 'statut'])]
class ContactMessage extends Model
{
    use HasUuids;
}
