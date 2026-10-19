<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->text('description');
            $table->string('categorie');
            $table->string('fournisseur')->default('DAG');
            $table->string('image')->nullable();
            $table->decimal('prix', 12, 2)->nullable();
            $table->enum('statut', ['BROUILLON', 'PUBLIE'])->default('PUBLIE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
