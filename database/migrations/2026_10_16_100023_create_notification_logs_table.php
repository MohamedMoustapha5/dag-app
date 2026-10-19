<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evenement');
            $table->enum('canal', ['EMAIL', 'WHATSAPP', 'SMS'])->default('EMAIL');
            $table->string('titre')->nullable();
            $table->text('message');
            $table->enum('statut', ['EN_ATTENTE', 'ENVOYE', 'ECHEC'])->default('EN_ATTENTE');
            $table->smallInteger('tentatives')->default(0);
            $table->dateTime('envoye_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
