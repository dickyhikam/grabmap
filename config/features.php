<?php

/**
 * Sakelar fitur yang sengaja belum dipakai.
 *
 * Dipakai untuk menyembunyikan fitur yang kodenya sudah ada tapi belum berlaku,
 * tanpa harus menghapusnya. Rute yang bersangkutan ikut tidak didaftarkan supaya
 * endpoint-nya benar-benar tertutup, bukan cuma tombolnya yang hilang.
 */
return [
    // Memasangkan API key ke perusahaan (assign/unassign).
    'api_key_assign' => env('FEATURE_API_KEY_ASSIGN', false),
];
