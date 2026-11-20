<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Uploaded images used to be saved with the full address of the site
     * they were uploaded on (http://localhost:8000/storage/...), which
     * breaks once the site moves to its real domain. They become relative
     * to the site root (/storage/...), like the built-in /images/... ones.
     */
    public function up(): void
    {
        foreach (['services', 'formations', 'formation_sessions'] as $table) {
            DB::table($table)
                ->where('image', 'like', 'http%/storage/%')
                ->select(['id', 'image'])
                ->get()
                ->each(fn (object $ligne) => DB::table($table)->where('id', $ligne->id)->update([
                    'image' => substr($ligne->image, strpos($ligne->image, '/storage/')),
                ]));
        }
    }

    /**
     * Nothing to undo: a relative address works on every domain.
     */
    public function down(): void
    {
        //
    }
};
