<?php

return [
    'title'          => 'Service charge',
    'sub_account'    => 'Tarif bawaan untuk semua perusahaan di akun ini',
    'sub_company'    => 'Biaya layanan yang ditagihkan ke perusahaan ini di atas biaya AWS',
    'percent'        => 'Persen dari biaya AWS',
    'min'            => 'Minimum per bulan',
    'effective'      => 'Berlaku mulai',
    'effective_hint' => 'Laporan sebelum tanggal ini tetap memakai tarif lama.',
    'how'            => 'Yang ditagih adalah yang lebih besar antara persen dan minimum. Minimum dipotong sesuai jumlah hari kalau laporannya tidak sebulan penuh. PPN dihitung dari biaya AWS + service charge.',
    'zero_hint'      => 'Isi 0 di keduanya untuk tanpa service charge.',
    'mode_inherit'   => 'Ikut tarif akun AWS',
    'mode_custom'    => 'Tarif khusus perusahaan ini',
    'inherit_none'   => 'akun belum punya tarif',
    'no_account'     => 'Perusahaan belum terhubung ke akun AWS — kalau tetap ikut tarif akun, tidak ada service charge.',
    'history'        => 'Riwayat tarif',
    'history_inherit'=> 'ikut akun',
    'none'           => 'Tanpa service charge',
    'by'             => 'oleh :name',
    'summary'        => ':pct · min :min / bulan',
    'err_percent'    => 'Persen harus antara 0 dan 100.',
    'err_min'        => 'Minimum tidak boleh negatif.',

    // Baris di laporan & invoice
    'incl'           => 'termasuk service charge & PPN :pct%',
    'line'           => 'Service charge',
    'basis_min'      => 'minimum :amount/bulan, prorata',
];
