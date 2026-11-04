<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom board dikelompokkan per kategori (belum trial / onboarding / live / berhenti).
 * Kategorinya tetap di kode (PipelineStage::CATEGORIES) — yang bebas diatur
 * hanya kolom mana masuk kategori mana.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'Setup & Integrasi' => 'onboarding',
        'Live'              => 'live',
        'Berhenti'          => 'closed',
    ];

    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('category', 20)->default('presales')->after('color');
        });

        // Sisanya (Prospek, Demo, Negosiasi, kolom buatan sendiri) tetap "belum trial".
        foreach (self::DEFAULTS as $name => $category) {
            DB::table('pipeline_stages')->where('name', $name)->update(['category' => $category]);
        }
    }

    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
