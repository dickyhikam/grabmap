<?php

/** Teks dashboard admin (template baru). */
return [
    'welcome'          => 'Selamat datang,',
    'refresh'          => 'Refresh data',
    'refresh_hint'     => 'Tarik ulang data rentang ini dari CloudWatch',
    'this_month'       => 'bulan ini',

    // Alert keadaan
    'aws_error'        => 'Gagal ambil data AWS: :error',
    'check_account'    => 'Cek akun',
    'budget_over'      => 'Ambang peringatan budget AWS terlampaui.',
    'budget_near'      => 'Mendekati ambang peringatan budget AWS.',
    'budget_body'      => 'Estimasi biaya :range :amount (≈ Rp :idr) sudah :pct% dari ambang :threshold.',
    'budget_edit'      => 'Ubah ambang',

    // Kartu biaya
    'cost_title'       => 'Biaya :range',
    'cost_sub'         => 'Estimasi termasuk PPN :pct%',
    'cost_brand'       => 'AWS LOCATION',
    'cost_total'       => 'Total estimasi',

    // Kartu request
    'requests_title'   => 'Request :range',
    'delta_halves'     => 'Paruh akhir :late vs paruh awal :early',
    'delta_none'       => 'Rentang terlalu pendek untuk dibandingkan',
    'delta_new'        => 'baru',

    // Grafik
    'chart_title'      => 'Permintaan Harian',
    'chart_sub'        => 'CloudWatch · :range',
    'chart_days'       => ':count Hari',
    'no_data'          => 'Belum ada data permintaan.',

    // Tabel operasi
    'ops_title'        => 'Rincian per Operasi',
    'ops_sub'          => 'Pemakaian × harga resmi AWS + PPN :pct%',
    'ops_op'           => 'Operasi',
    'subtotal'         => 'Subtotal',
    'vat'              => 'PPN :pct%',
    'total_vat'        => 'Total + PPN',
    'ops_note'         => 'Estimasi bisa meleset ~5% dari tagihan final.',
    'fetched_at'       => 'Data per :time WIB.',
    'no_snapshot'      => 'Belum ada data — klik Refresh.',

    // Budget
    'budget_title'     => 'Status Budget',
    'budget_threshold' => 'Ambang AWS Budgets :amount',
    'budget_used'      => 'Terpakai :range',

    // Kategori
    'cat_title'        => 'Biaya per Kategori',
    'cat_sub'          => 'Sebelum PPN',
    'cat_maps'         => 'Maps',
    'cat_places'       => 'Places / Search',
    'cat_routes'       => 'Routes',
    'requests_word'    => 'request',

    // Top pemakai & akun
    'top_title'        => 'API Key Paling Banyak Dipakai',
    'top_sub'          => 'Peringkat pemakaian :range',
    'no_data_short'    => 'Belum ada data',
    'by_account'       => 'Pemakaian per Akun AWS',
    'accounts_active'  => ':count akun aktif',

    'key_budget_over' => 'Batas biaya key :key terlampaui',
    'key_budget_near' => 'Key :key mendekati batas biaya',
    'key_budget_body' => ':amount dari :threshold (:pct%) pada :range.',
    'key_budget_open' => 'Buka key',
];
