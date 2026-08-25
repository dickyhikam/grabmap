<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu perusahaan bisa memegang banyak API key. Sebelumnya hubungan itu hanya
 * satu kolom di tabel companies (aws_api_key_name), sehingga laporan pemakaian
 * yang dibagikan ke klien selalu per satu key.
 *
 * Pasangan (akun, nama key) dibuat unik: satu key hanya boleh menempel ke satu
 * perusahaan, supaya biayanya tidak terhitung dobel di dua laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Null = kredensial .env (belum ada akun AWS tersimpan).
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('key_name');
            $table->string('label')->nullable();
            // Key utama = yang dipakai halaman peta klien.
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['aws_account_id', 'key_name']);
            $table->index('company_id');
        });

        // Pindahkan hubungan lama (satu key per company) ke tabel baru.
        DB::table('companies')
            ->whereNotNull('aws_api_key_name')
            ->orderBy('id')
            ->get(['id', 'aws_account_id', 'aws_api_key_name'])
            ->each(function ($company) {
                $exists = DB::table('company_api_keys')
                    ->where('aws_account_id', $company->aws_account_id)
                    ->where('key_name', $company->aws_api_key_name)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('company_api_keys')->insert([
                    'company_id'     => $company->id,
                    'aws_account_id' => $company->aws_account_id,
                    'key_name'       => $company->aws_api_key_name,
                    'is_primary'     => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_api_keys');
    }
};
