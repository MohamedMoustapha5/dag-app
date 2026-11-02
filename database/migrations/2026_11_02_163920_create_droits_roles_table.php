<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-role access overrides on top of the default matrix of the cahier
     * des charges (section 3.2). A missing row means "use the default", so
     * the matrix stays editable from the admin without a code change.
     */
    public function up(): void
    {
        Schema::create('droits_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);
            $table->string('donnee', 60);
            $table->string('niveau', 20);
            $table->timestamps();
            $table->unique(['role', 'donnee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('droits_roles');
    }
};
