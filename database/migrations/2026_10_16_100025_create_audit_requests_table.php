<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('page_url');
            $table->string('email');
            $table->enum('statut', ['NOUVELLE', 'TRAITEE'])->default('NOUVELLE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_requests');
    }
};
