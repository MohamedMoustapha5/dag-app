<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUuid('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignUuid('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->date('date_emission');
            $table->string('fichier_pdf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
