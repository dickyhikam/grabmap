<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin ".manage" dipecah jadi aksi (create/update/delete/…), jadi role yang sudah
 * ada perlu dipetakan ulang. Tanpa ini, role non-admin kehilangan aksesnya diam-diam.
 */
return new class extends Migration
{
    /** Kunci lama => kunci baru yang menggantikannya. */
    private const MAP = [
        'companies.manage'     => ['companies.create', 'companies.update'],
        'api_keys.manage'      => ['api_keys.create', 'api_keys.update', 'api_keys.assign'],
        'aws_accounts.manage'  => ['aws_accounts.create', 'aws_accounts.update', 'aws_accounts.delete'],
        'cost_settings.manage' => ['cost_settings.view', 'cost_settings.update'],
        'users.manage'         => ['users.view', 'users.create', 'users.update', 'users.delete', 'users.credentials'],
        'roles.manage'         => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
    ];

    public function up(): void
    {
        $this->remap(self::MAP);
    }

    public function down(): void
    {
        $reverse = [];
        foreach (self::MAP as $old => $news) {
            foreach ($news as $new) {
                $reverse[$new] = [$old];
            }
        }

        $this->remap($reverse);
    }

    private function remap(array $map): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            // Role berakses penuh ('*') tidak perlu disentuh.
            if (in_array('*', $permissions, true)) {
                continue;
            }

            $next = [];
            foreach ($permissions as $permission) {
                foreach ($map[$permission] ?? [$permission] as $replacement) {
                    $next[] = $replacement;
                }
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($next))),
                'updated_at'  => now(),
            ]);
        }
    }
};
