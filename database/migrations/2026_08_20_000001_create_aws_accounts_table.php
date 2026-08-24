<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aws_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number')->nullable();      // 12 digit AWS account id (informasi saja)
            $table->string('access_key_id')->nullable();
            $table->text('secret_access_key')->nullable();      // disimpan terenkripsi (cast 'encrypted')
            $table->string('region')->default('ap-southeast-1');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });

        // Pindahkan kredensial yang selama ini di .env jadi akun default,
        // supaya halaman API Key & usage tetap jalan tanpa setup ulang.
        $key    = env('AWS_ACCESS_KEY_ID');
        $secret = env('AWS_SECRET_ACCESS_KEY');

        if (!empty($key) && !empty($secret)) {
            DB::table('aws_accounts')->insert([
                'name'              => 'Akun Utama',
                'access_key_id'     => $key,
                'secret_access_key' => Crypt::encryptString($secret),
                'region'            => env('AWS_REGION', 'ap-southeast-1'),
                'is_active'         => true,
                'is_default'        => true,
                'notes'             => 'Dibuat otomatis dari kredensial .env saat migrasi multi-akun.',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aws_accounts');
    }
};
