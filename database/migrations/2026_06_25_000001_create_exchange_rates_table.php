<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 12, 2);                 // Rp per 1 USD
            $table->date('rate_date');                      // kurs berlaku per tanggal
            $table->string('source')->default('Manual');    // sumber: Kurs Pajak / Bank Indonesia / Manual
            $table->string('reference')->nullable();        // no. KMK / link bukti
            $table->text('note')->nullable();               // catatan
            $table->boolean('is_active')->default(false);   // kurs yang sedang dipakai
            $table->string('created_by')->nullable();       // siapa yang input
            $table->timestamp('created_at')->useCurrent();

            $table->index(['is_active', 'rate_date']);
        });

        // Seed kurs awal (dari config) supaya halaman usage langsung punya nilai.
        DB::table('exchange_rates')->insert([
            'rate'       => (float) config('aws.usd_to_idr', 16500),
            'rate_date'  => now()->toDateString(),
            'source'     => 'Manual',
            'reference'  => null,
            'note'       => 'Kurs awal default sistem.',
            'is_active'  => true,
            'created_by' => 'system',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
