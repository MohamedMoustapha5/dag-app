<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * With the database session driver, each session records who is signed in:
 * a client or apprenant (integer id) or a DAG team member (UUID). An integer
 * column refuses the UUID on MySQL and PostgreSQL, which breaks every
 * request of the administration once SESSION_DRIVER=database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
