<?php

/**
 * Mata uang lokal per locale halaman /pricing.
 *
 * Nilai `rate` di sini hanya NILAI AWAL kolom kurs — pengunjung bisa
 * menggantinya langsung di halaman, dan pilihannya tersimpan di browser
 * masing-masing. Jadi angka di bawah tidak perlu dijaga tetap mutakhir;
 * ia cuma titik mulai yang masuk akal.
 *
 * Khusus IDR, nilai awalnya ditimpa kurs aktif di tabel `exchange_rates`
 * (dipakai modul tagihan) supaya halaman ini dan invoice memakai angka sama.
 *
 * decimals: 0 untuk mata uang yang tidak lazim memakai sen.
 * js_locale: dipakai Intl.NumberFormat supaya pemisah ribuannya sesuai negara
 * (Rp 2.004.800, bukan Rp 2,004,800).
 */
return [
    'en'  => ['code' => 'SGD', 'symbol' => 'S$',  'rate' => 1.35,    'decimals' => 2, 'js_locale' => 'en-SG'],
    'id'  => ['code' => 'IDR', 'symbol' => 'Rp',  'rate' => 17900,   'decimals' => 0, 'js_locale' => 'id-ID'],
    'ms'  => ['code' => 'MYR', 'symbol' => 'RM',  'rate' => 4.7,     'decimals' => 2, 'js_locale' => 'ms-MY'],
    'fil' => ['code' => 'PHP', 'symbol' => '₱',   'rate' => 58,      'decimals' => 2, 'js_locale' => 'fil-PH'],
    'th'  => ['code' => 'THB', 'symbol' => '฿',   'rate' => 36,      'decimals' => 2, 'js_locale' => 'th-TH'],
    'vi'  => ['code' => 'VND', 'symbol' => '₫',   'rate' => 26000,   'decimals' => 0, 'js_locale' => 'vi-VN'],
    'my'  => ['code' => 'MMK', 'symbol' => 'K',   'rate' => 4700,    'decimals' => 0, 'js_locale' => 'my-MM'],
    'km'  => ['code' => 'KHR', 'symbol' => '៛',   'rate' => 4100,    'decimals' => 0, 'js_locale' => 'km-KH'],
];
