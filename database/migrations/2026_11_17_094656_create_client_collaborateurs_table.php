<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People an Entreprise or Organisation client lets into its espace. Each row
 * links a user account of its own (its own e-mail and password) to the
 * client it works for; that account only exists for this access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_collaborateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('invite_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepte_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_collaborateurs');
    }
};
