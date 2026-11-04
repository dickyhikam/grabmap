<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Board pipeline perusahaan: kolom (tahap) disimpan di database supaya bisa
 * ditambah, diganti nama, dan diurutkan dari UI — sama seperti list di Trello.
 * Posisi perusahaan di board menempel langsung di tabel companies.
 */
return new class extends Migration
{
    /** Tahap bawaan: alur sales lalu teknis, dari prospek sampai jalan. */
    private const DEFAULTS = [
        ['name' => 'Prospek',           'color' => 'slate'],
        ['name' => 'Demo',              'color' => 'indigo'],
        ['name' => 'Negosiasi',         'color' => 'amber'],
        ['name' => 'Setup & Integrasi', 'color' => 'sky'],
        ['name' => 'Live',              'color' => 'green'],
        ['name' => 'Berhenti',          'color' => 'red'],
    ];

    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 60);
            $table->string('color', 20)->default('slate');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('pipeline_stage_id')->nullable()->after('is_active')
                ->constrained('pipeline_stages')->nullOnDelete();
            $table->unsignedInteger('pipeline_position')->default(0)->after('pipeline_stage_id');
            $table->timestamp('stage_changed_at')->nullable()->after('pipeline_position');
            // PIC dari sisi kita — siapa yang mengawal perusahaan ini.
            $table->foreignId('owner_id')->nullable()->after('stage_changed_at')
                ->constrained('users')->nullOnDelete();
            $table->string('next_action')->nullable()->after('owner_id');
            $table->date('next_action_due')->nullable()->after('next_action');
        });

        $now = now();
        $ids = [];
        foreach (self::DEFAULTS as $i => $stage) {
            $ids[$stage['name']] = DB::table('pipeline_stages')->insertGetId($stage + [
                'uuid'       => (string) Str::uuid(),
                'position'   => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Tebakan awal untuk perusahaan yang sudah ada — tinggal digeser kalau
        // meleset: punya API key = sudah jalan, akun AWS klien terhubung tanpa
        // key = sedang setup, halaman demo aktif = tahap demo, sisanya prospek.
        $withKeys  = DB::table('company_api_keys')->distinct()->pluck('company_id')->all();
        $withOwnAws = DB::table('aws_accounts')->whereNotNull('owner_company_id')->distinct()->pluck('owner_company_id')->all();

        foreach (DB::table('companies')->orderBy('id')->get() as $i => $company) {
            $stage = match (true) {
                in_array($company->id, $withKeys)   => 'Live',
                in_array($company->id, $withOwnAws) => 'Setup & Integrasi',
                (bool) $company->is_active          => 'Demo',
                default                             => 'Prospek',
            };

            DB::table('companies')->where('id', $company->id)->update([
                'pipeline_stage_id' => $ids[$stage],
                'pipeline_position' => $i,
                'stage_changed_at'  => $company->created_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_stage_id');
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['pipeline_position', 'stage_changed_at', 'next_action', 'next_action_due']);
        });

        Schema::dropIfExists('pipeline_stages');
    }
};
