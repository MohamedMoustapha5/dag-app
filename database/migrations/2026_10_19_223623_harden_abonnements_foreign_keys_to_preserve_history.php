<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * client_id/entreprise_id/formule_id cascaded on delete, unlike the
     * identical relationships on demandes/factures/contrats which already
     * set null to preserve the billing trail. This left subscription
     * history reachable for deletion through the account self-deletion
     * feature (deleting a user cascades to its client, which cascaded to
     * its abonnements).
     */
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['entreprise_id']);
            $table->dropForeign(['formule_id']);
        });

        Schema::table('abonnements', function (Blueprint $table) {
            $table->uuid('formule_id')->nullable()->change();
        });

        Schema::table('abonnements', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('entreprise_id')->references('id')->on('entreprises_partenaires')->nullOnDelete();
            $table->foreign('formule_id')->references('id')->on('formules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['entreprise_id']);
            $table->dropForeign(['formule_id']);
        });

        Schema::table('abonnements', function (Blueprint $table) {
            $table->uuid('formule_id')->nullable(false)->change();
        });

        Schema::table('abonnements', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('entreprise_id')->references('id')->on('entreprises_partenaires')->cascadeOnDelete();
            $table->foreign('formule_id')->references('id')->on('formules')->cascadeOnDelete();
        });
    }
};
