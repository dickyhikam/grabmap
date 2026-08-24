<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Role pindah dari kolom string 'users.role' ke tabel tersendiri.
 *
 * Kolom lama dihapus di akhir supaya tidak ada dua sumber kebenaran — kalau ada kode
 * yang terlewat, ia gagal keras (kolom hilang), bukan diam-diam membaca data basi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->string('description')->nullable();
            $table->string('color', 20)->default('slate');
            $table->json('permissions')->nullable();
            // Role sistem tidak bisa dihapus; 'admin' juga selalu berizin penuh.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert([
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'Administrator',
                'slug'        => 'admin',
                'description' => 'Akses penuh ke seluruh panel admin.',
                'color'       => 'violet',
                'permissions' => json_encode(['*']),
                'is_system'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'Operator',
                'slug'        => 'user',
                'description' => 'Hanya melihat data, tanpa mengubah pengaturan.',
                'color'       => 'green',
                'permissions' => json_encode([
                    'dashboard.view', 'companies.view', 'api_keys.view',
                    'aws_accounts.view', 'simulator.use',
                ]),
                'is_system'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        $roleIdBySlug = DB::table('roles')->pluck('id', 'slug');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
        });

        // Pindahkan nilai lama; apa pun selain 'admin' (termasuk null) jadi operator.
        DB::table('users')->where('role', 'admin')->update(['role_id' => $roleIdBySlug['admin']]);
        DB::table('users')->whereNull('role_id')->update(['role_id' => $roleIdBySlug['user']]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
        });

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminId) {
            DB::table('users')->where('role_id', $adminId)->update(['role' => 'admin']);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
