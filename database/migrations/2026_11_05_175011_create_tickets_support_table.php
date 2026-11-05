<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A support request: opened by a client or an apprenant from their espace,
     * or by a visitor through the public contact form (user_id null). The
     * name and e-mail are copied at opening so the team can always answer.
     */
    public function up(): void
    {
        Schema::create('tickets_support', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('origine', ['ESPACE', 'PUBLIC'])->default('ESPACE');
            $table->string('nom');
            $table->string('email');
            $table->enum('categorie', ['FACTURATION', 'CONTRAT', 'DEMANDE', 'FORMATION', 'COMPTE', 'AUTRE'])->default('AUTRE');
            $table->string('sujet');
            $table->enum('statut', ['OUVERT', 'REPONDU', 'CLOS'])->default('OUVERT');
            $table->dateTime('dernier_message_at')->nullable();
            $table->timestamps();

            $table->index(['statut', 'dernier_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_support');
    }
};
