<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The partner logos of the public site (home band, « À propos »), managed
     * by the team. The three logos shown so far are carried over, so the
     * site looks the same once deployed.
     */
    public function up(): void
    {
        Schema::create('logos_partenaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('logo');
            $table->string('lien')->nullable();
            $table->boolean('fond_sombre')->default(false);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        $logos = [
            ['Nova-Tech', '/images/brand/partners/nova-tech-logo.png', false],
            ['Nordex Consulting Group', '/images/brand/partners/nordex-consulting-group-logo.jpg', true],
            ['Genis Academic', '/images/brand/partners/genis-academic.png', false],
        ];

        foreach ($logos as $ordre => [$nom, $logo, $fondSombre]) {
            DB::table('logos_partenaires')->insert([
                'id' => (string) Str::uuid7(),
                'nom' => $nom,
                'logo' => $logo,
                'fond_sombre' => $fondSombre,
                'visible' => true,
                'ordre' => $ordre + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logos_partenaires');
    }
};
