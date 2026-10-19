<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apprenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->date('date_naissance')->nullable();
            $table->string('telephone')->nullable();
            $table->string('ville')->nullable();
            $table->string('cv_path')->nullable();
            $table->boolean('consentement_cv')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apprenants');
    }
};
