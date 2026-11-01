<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notif_seances')->default(true)->after('notif_offres');
            $table->boolean('notif_candidatures')->default(true)->after('notif_seances');
            $table->boolean('notif_newsletter')->default(false)->after('notif_candidatures');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notif_seances', 'notif_candidatures', 'notif_newsletter']);
        });
    }
};
