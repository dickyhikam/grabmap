<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keterangan per kolom board pipeline — supaya semua orang sepakat kapan sebuah
 * perusahaan pantas dipindah ke tahap itu.
 */
return new class extends Migration
{
    /** Hanya mengisi tahap bawaan yang namanya belum diganti. */
    private const DEFAULTS = [
        'Prospek'           => 'Calon klien yang sudah dikontak, belum melihat demo.',
        'Demo'              => 'Sudah dijadwalkan atau sudah presentasi dan mencoba halaman demo.',
        'Negosiasi'         => 'Membahas harga, penawaran, dan kontrak.',
        'Setup & Integrasi' => 'Sudah deal — API key, akun AWS, dan integrasi sedang disiapkan.',
        'Live'              => 'Sudah dipakai di produksi dan ada pemakaian.',
        'Berhenti'          => 'Tidak lanjut, atau berhenti berlangganan.',
    ];

    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        foreach (self::DEFAULTS as $name => $description) {
            DB::table('pipeline_stages')->where('name', $name)->whereNull('description')->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
