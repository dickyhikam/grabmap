<?php

/**
 * Teks milik kerangka admin baru (layouts/admin-v2 + partial-nya):
 * rail, topbar, command palette, pemilih tanggal, dan tombol umum.
 * Teks khusus per halaman ada di file lain (mis. users.php).
 */
return [
    // Rail & topbar
    'aws_accounts'      => 'Akun AWS',
    'cost_settings'     => 'Kurs & Pajak',
    'simulator'         => 'Simulator',
    'roles'             => 'Role & Akses',
    'users'             => 'Pengguna',
    'open_homepage'     => 'Buka homepage',
    'api_tester'        => 'API Tester',
    'sign_out'          => 'Keluar',
    'homepage'          => 'Homepage',
    'theme_light'       => 'Mode terang',
    'theme_dark'        => 'Mode gelap',
    'theme_system'      => 'Ikut sistem',
    'role_admin'        => 'Administrator',
    'role_operator'     => 'Operator',
    'verified'          => 'Terverifikasi',
    'unverified'        => 'Belum verifikasi',

    // Pemilih akun AWS
    'aws_scope_title'   => 'Data yang ditampilkan',
    'aws_scope_hint'    => 'Akun AWS yang datanya ditampilkan',
    'aws_env_creds'     => 'Kredensial .env',
    'aws_no_account'    => 'Belum ada akun tersimpan di database',
    'aws_no_creds'      => 'belum ada kredensial',
    'aws_active'        => 'Aktif',
    'aws_view'          => 'Lihat',
    'aws_manage'        => 'Kelola akun & atur default',
    'loading'           => 'Memuat…',

    // Command palette
    'search'            => 'Cari',
    'search_hint'       => 'Cari (⌘K)',
    'search_placeholder' => 'Cari perusahaan, akun AWS, halaman…',
    'search_pages'      => 'Halaman',
    'search_actions'    => 'Aksi cepat',
    'search_page_sub'   => 'Halaman admin',
    'search_no_result'  => 'Tidak ada hasil untuk “:query”',
    'search_pick'       => 'pilih',
    'search_open'       => 'buka',
    'search_close'      => 'tutup',
    'action_add_company'    => 'Tambah perusahaan',
    'action_add_company_d'  => 'Buat company baru',
    'action_add_key'        => 'Tambah API key',
    'action_add_key_d'      => 'Buat API key di AWS',
    'action_refresh'        => 'Refresh data AWS',
    'action_refresh_d'      => 'Tarik ulang CloudWatch',
    'action_homepage_d'     => 'Peta publik',
    'action_tester_d'       => 'Uji endpoint',
    'companies'         => 'Perusahaan',

    // Pemilih rentang tanggal
    'range_this_month'  => 'Bulan ini',
    'range_days'        => ':count hari',
    'range_last_days'   => ':count hari',
    'range_prev_month'  => 'Bulan lalu',
    'range_note'        => 'Rentang baru menarik data dari CloudWatch.',
    'range_max'         => '(maksimum)',

    // Umum
    'hour'              => 'Jam',
    'minute'            => 'Menit',
    'pick_date'         => 'Pilih tanggal',
    'no_limit'          => 'Tanpa batas',
    'per_month'         => 'per bulan',
    'no_access'         => 'Anda tidak punya akses ke bagian ini.',
    'cancel'            => 'Batal',
    'apply'             => 'Terapkan',
    'save'              => 'Simpan perubahan',
    'prev'              => 'Sebelumnya',
    'next'              => 'Berikutnya',
    'close'             => 'Tutup',
    'show_password'     => 'Tampilkan kata sandi',
    'hide_password'     => 'Sembunyikan kata sandi',
    'generate'          => 'Buat acak',
];
