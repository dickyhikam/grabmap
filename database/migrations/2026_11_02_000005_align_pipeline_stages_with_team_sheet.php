<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Samakan kolom bawaan dengan istilah yang dipakai tim di lembar status klien:
 * Prospek · Meeting · POC · Integrasi · Subscribed · Drop.
 *
 * Kolom yang sudah diganti nama sendiri tidak disentuh, dan keterangan hanya
 * ditimpa kalau masih keterangan bawaan.
 */
return new class extends Migration
{
    /** nama lama => [nama baru, kategori, warna, keterangan lama, keterangan baru] */
    private const RENAMES = [
        'Prospek' => [
            'Prospek', 'presales', 'slate',
            'Calon klien yang sudah dikontak, belum melihat demo.',
            'Calon klien yang belum diajak meeting.',
        ],
        'Demo' => [
            'Meeting', 'presales', 'indigo',
            'Sudah dijadwalkan atau sudah presentasi dan mencoba halaman demo.',
            'Sudah meeting atau presentasi; kebutuhan dan klarifikasi sedang dibahas.',
        ],
        'Negosiasi' => [
            'POC', 'onboarding', 'amber',
            'Membahas harga, penawaran, dan kontrak.',
            'Klien sedang uji coba dengan API key atau akun AWS-nya sendiri.',
        ],
        'Setup & Integrasi' => [
            'Integrasi', 'onboarding', 'sky',
            'Sudah deal — API key, akun AWS, dan integrasi sedang disiapkan.',
            'POC lolos — integrasi ke aplikasi klien dan administrasi (NDA, kontrak) berjalan.',
        ],
        'Live' => [
            'Subscribed', 'live', 'green',
            'Sudah dipakai di produksi dan ada pemakaian.',
            'Sudah berlangganan dan dipakai di produksi.',
        ],
        'Berhenti' => [
            'Drop', 'closed', 'red',
            'Tidak lanjut, atau berhenti berlangganan.',
            'Tidak lanjut. Kartu mencatat di tahap mana klien berhenti.',
        ],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => [$name, $category, $color, $oldDesc, $newDesc]) {
            $stage = DB::table('pipeline_stages')->where('name', $old)->first();
            if (!$stage) {
                continue;
            }

            DB::table('pipeline_stages')->where('id', $stage->id)->update([
                'name'        => $name,
                'category'    => $category,
                'color'       => $color,
                'description' => in_array($stage->description, [null, $oldDesc], true) ? $newDesc : $stage->description,
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => [$name, , , $oldDesc, $newDesc]) {
            DB::table('pipeline_stages')->where('name', $name)->update(['name' => $old]);
            DB::table('pipeline_stages')->where('name', $old)->where('description', $newDesc)->update(['description' => $oldDesc]);
        }
        DB::table('pipeline_stages')->where('name', 'Negosiasi')->update(['category' => 'presales']);
    }
};
