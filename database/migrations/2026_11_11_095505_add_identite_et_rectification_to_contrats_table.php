<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The DAG contract (as written by the juriste) identifies the client by
 * phone, ID card and place of service, given by the client when signing,
 * and allows two requests for rectification before a new contract is due.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('telephone_cocontractant', 30)->nullable()->after('signataire_nom');
            $table->string('cni_numero', 50)->nullable()->after('telephone_cocontractant');
            $table->string('cni_lieu_delivrance', 120)->nullable()->after('cni_numero');
            $table->date('cni_date_delivrance')->nullable()->after('cni_lieu_delivrance');
            $table->string('ville_residence', 120)->nullable()->after('cni_date_delivrance');
            $table->string('quartier_prestation', 120)->nullable()->after('ville_residence');
            $table->unsignedTinyInteger('nombre_rectifications')->default(0)->after('quartier_prestation');
            $table->text('motif_rectification')->nullable()->after('nombre_rectifications');
            $table->dateTime('rectification_demandee_at')->nullable()->after('motif_rectification');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn([
                'telephone_cocontractant', 'cni_numero', 'cni_lieu_delivrance', 'cni_date_delivrance',
                'ville_residence', 'quartier_prestation', 'nombre_rectifications', 'motif_rectification',
                'rectification_demandee_at',
            ]);
        });
    }
};
