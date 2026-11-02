<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff accounts get their own credentials on membres_equipe itself
     * instead of sharing the public `users` table with clients and
     * apprenants: an attack on the public login (credential stuffing, a
     * leaked users dump) no longer reaches administration accounts. Existing
     * staff rows are carried over before their `users` rows are removed.
     */
    public function up(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
            $table->string('email')->nullable()->after('name');
            $table->string('password')->default('')->after('email');
            $table->rememberToken();
        });

        $membres = DB::table('membres_equipe')
            ->join('users', 'users.id', '=', 'membres_equipe.user_id')
            ->select('membres_equipe.id as membre_id', 'users.id as user_id', 'users.name', 'users.email', 'users.password')
            ->get();

        foreach ($membres as $membre) {
            DB::table('membres_equipe')->where('id', $membre->membre_id)->update([
                'name' => $membre->name,
                'email' => $membre->email,
                'password' => $membre->password,
            ]);
        }

        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->unique('email');
        });

        DB::table('users')->whereIn('id', $membres->pluck('user_id'))->delete();
    }

    public function down(): void
    {
        throw new RuntimeException('Les comptes administration ne peuvent pas être refusionnés dans la table users.');
    }
};
