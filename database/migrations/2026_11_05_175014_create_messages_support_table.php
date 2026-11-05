<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One message of a support thread: written by the person who opened the
     * ticket (DEMANDEUR) or by a DAG team member (EQUIPE).
     */
    public function up(): void
    {
        Schema::create('messages_support', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_support_id')->constrained('tickets_support')->cascadeOnDelete();
            $table->enum('auteur', ['DEMANDEUR', 'EQUIPE']);
            $table->foreignUuid('membre_equipe_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->text('contenu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages_support');
    }
};
