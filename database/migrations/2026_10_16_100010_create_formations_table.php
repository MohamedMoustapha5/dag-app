<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->text('description');
            $table->text('programme')->nullable();
            $table->string('categorie')->nullable();
            $table->string('duree')->nullable();
            $table->string('prerequis')->nullable();
            $table->string('image')->nullable();
            $table->enum('statut', ['BROUILLON', 'PUBLIEE', 'EN_COURS', 'TERMINEE'])->default('PUBLIEE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
