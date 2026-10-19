<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('formation_session_id')->constrained('formation_sessions')->cascadeOnDelete();
            $table->string('titre')->nullable();
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
