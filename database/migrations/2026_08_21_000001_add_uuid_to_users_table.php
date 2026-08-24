<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * UUID publik untuk user — dipakai sebagai route key supaya id auto-increment
 * tidak pernah muncul di URL (/admin/users/9f3c…/edit, bukan /admin/users/7/edit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Isi baris yang sudah ada; tanpa ini route binding-nya tidak ketemu.
        DB::table('users')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
