<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isian dari lembar status klien tim: kebutuhan, website, PIC dari tim Grab,
 * tautan notulen meeting, dan daftar pertanyaan klien yang perlu dijawab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('website')->nullable()->after('next_action_due');
            $table->text('needs')->nullable()->after('website');
            // PIC dari tim Grab (GFB) — orang luar aplikasi, jadi teks bebas.
            $table->string('pic_external', 120)->nullable()->after('needs');
            $table->json('mom_links')->nullable()->after('pic_external');
            // Nama tahap terakhir sebelum masuk kategori "berhenti" (mis. "POC").
            $table->string('closed_from', 60)->nullable()->after('mom_links');
        });

        Schema::create('company_clarifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('status', 12)->default('open');   // open | waiting | clarified
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_clarifications');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['website', 'needs', 'pic_external', 'mom_links', 'closed_from']);
        });
    }
};
