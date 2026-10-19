<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('seance_id')->constrained('seances')->cascadeOnDelete();
            $table->foreignUuid('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->boolean('present')->default(false);
            $table->timestamps();
            $table->unique(['seance_id', 'inscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
