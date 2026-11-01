<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatures_spontanees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('email');
            $table->string('telephone');
            $table->string('portfolio')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path');
            $table->enum('statut', ['NOUVELLE', 'TRAITEE'])->default('NOUVELLE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures_spontanees');
    }
};
