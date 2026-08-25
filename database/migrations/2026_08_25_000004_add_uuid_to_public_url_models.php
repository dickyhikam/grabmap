<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * URL admin tidak boleh membocorkan id auto-increment — jumlah dan urutan baris
 * jangan terbaca dari alamat halaman. Pola ini sudah dipakai tabel users; di sini
 * menyusul tabel lain yang idnya muncul di URL.
 */
return new class extends Migration
{
    private array $tables = ['companies', 'aws_accounts', 'exchange_rates'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->after('id');
            });

            DB::table($table)->whereNull('uuid')->orderBy('id')->pluck('id')
                ->each(fn ($id) => DB::table($table)->where('id', $id)->update(['uuid' => (string) Str::uuid()]));

            Schema::table($table, function (Blueprint $t) {
                $t->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique($table . '_uuid_unique');
                $t->dropColumn('uuid');
            });
        }
    }
};
