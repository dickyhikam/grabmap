<?php

/**
 * Teks antarmuka halaman /tester-api.
 *
 * Nama parameter API (Bias Latitude, Device ID, Font Stack, dan sejenisnya)
 * sengaja TIDAK diterjemahkan — itu nama field milik AWS, dan menerjemahkannya
 * membuat halaman ini sulit dicocokkan dengan dokumentasi resminya.
 */
return [
    'title' => 'Penguji API AWS Location Service',
    'subtitle' => 'Uji dan telusuri API AWS Location Service — Routes, Places, Maps, Geofencing, dan Tracking (Grab)',
    'back_to_map' => 'Kembali ke peta',

    'theme_light' => 'Mode terang',
    'theme_dark' => 'Mode gelap',
    'theme_system' => 'Ikut sistem',
    'language' => 'Bahasa',

    'api_key' => 'API Key',
    'api_key_saved' => 'API Key tersimpan',
    'api_key_missing' => 'API Key belum diisi',
    'api_reference' => 'Rujukan API',

    'rail_route' => 'Hitung Rute',
    'rail_matrix' => 'Matriks Rute',
    'rail_places' => 'Tempat',
    'rail_maps' => 'Peta',
    'rail_hide' => 'Sembunyikan panel',

    'request' => 'Permintaan',
    'response' => 'Balasan',
    'request_history' => 'Riwayat Permintaan',
    'no_requests' => 'Belum ada permintaan',
    'request_placeholder' => '// Tekan "Kirim" untuk melihat isi permintaannya di sini',
    'response_placeholder' => '// Balasannya akan muncul di sini',
    'clear' => 'Bersihkan',
    'copy' => 'Salin',

    'gate_title' => 'Perlu API Key',
    'gate_subtitle' => 'Masukkan API key AWS Location Service untuk melanjutkan',
    'gate_note' => 'Penguji ini butuh API key AWS untuk menampilkan peta dan memanggil API-nya. Key kamu disimpan di browser ini (localStorage) dan tidak pernah dikirim ke server kami.',
    'gate_label' => 'API Key AWS Location Service',
    'gate_how' => 'Bagaimana cara dapat API Key?',
    'gate_continue' => 'Lanjutkan',
    'gate_empty' => 'API key tidak boleh kosong',

    // Keterangan tiap mode dan petunjuk singkat di panel kiri.
    'mode_route_desc' => 'Rute tunggal A&rarr;B dengan waypoint opsional. Mengembalikan <b>geometri penuh (LineString)</b>, jarak, durasi, dan detail tiap leg. Cocok untuk <b>menggambar rute di peta</b>.',
    'mode_matrix_desc' => 'Matriks N origin &times; M destination. Mengembalikan <b>jarak + durasi</b> per pasangan saja, tanpa geometri. Cocok untuk <b>mengurutkan perhentian, mencari yang terdekat, dan perbandingan borongan</b>.',
    'mode_places_desc' => 'Cari tempat, autocomplete, reverse geocode, dan ambil detail tempat (v2 mandiri).',
    'mode_maps_desc' => 'Telusuri sumber daya peta: <b>style descriptor, tile, glyph, sprite</b>.',
    'mode_geofence_desc' => 'Kelola geofence dan evaluasi posisi. Tidak bergantung penyedia (asli AWS).',
    'mode_tracking_desc' => 'Lacak posisi perangkat dan ambil riwayatnya. Tidak bergantung penyedia (asli AWS).',
    'mode_routes_desc' => 'Hitung rute dan matriks rute — tanpa batas jarak.',
    'hint_tile' => 'Mengembalikan vector tile PBF. Keterangan balasannya akan ditampilkan.',
    'hint_glyph' => 'Mengembalikan data glyph PBF untuk menggambar label peta.',
    'hint_ring' => 'Koordinat pertama dan terakhir harus sama (cincin tertutup).',
    'hint_eval_click' => 'Tekan peta untuk menentukan posisi yang dievaluasi.',
    'hint_device_click' => 'Tekan peta untuk menentukan posisi perangkat. Cap waktunya dibuat otomatis.',
    'maps_styles_note' => 'Tersedia 2 gaya peta — vektor saja, tanpa citra satelit.',

    // Panel isian rute dan penanda di peta.
    'pick_from_map' => 'Pilih dari peta',
    'from_map' => 'Dari peta',
    'add' => 'Tambah',
    'departure_position' => 'Posisi Berangkat',
    'destination_position' => 'Posisi Tujuan',
    'waypoints' => 'Titik Singgah',
    'waypoints_route_only' => '(khusus Route)',
    'no_waypoints' => 'Belum ada titik singgah (opsional)',
    'map_hint' => 'Tekan <b>Pilih dari peta</b>, lalu tekan petanya untuk menentukan koordinat.',
    'legend_departure' => 'Berangkat',
    'legend_destination' => 'Tujuan',
    'legend_route' => 'Rute',
    'legend_result' => 'Hasil lokasi',
    'api_endpoint' => 'ENDPOINT API',
    'route_matrix_input' => 'ISIAN RUTE & MATRIKS',
    'send_route_request' => 'Kirim Permintaan Rute',

    // Petunjuk di bawah peta, per mode.
    'hint_route' => 'Tekan <b>Pilih dari peta</b>, lalu tekan petanya untuk menentukan koordinat.',
    'hint_matrix' => 'Tekan <b>Pilih dari peta</b> atau <b>Tambah dari peta</b>, lalu tekan petanya untuk menentukan koordinat.',
    'hint_location' => 'Tekan peta untuk mengisi koordinat reverse geocode. Hasil pencarian muncul sebagai penanda.',
    'hint_maps' => 'Tekan "Fill from map center" untuk mengambil koordinat tile dari tampilan peta saat ini.',
    'hint_geofence' => 'Tekan peta untuk menentukan pusat geofence atau posisi yang dievaluasi. Batas geofence digambar sebagai poligon.',
    'hint_tracking' => 'Tekan peta untuk menentukan posisi perangkat. Penanda perangkat dan jejak riwayatnya digambar di peta.',
    'legend_geofence' => 'Geofence',
    'legend_tracking' => 'Pelacakan',

    // Tombol kirim dan judul daftar hasil.
    'send_request' => 'Kirim Permintaan :mode',
    'geofences' => 'Geofence',
    'devices' => 'Perangkat',

    // Penanda versi API di panel kiri.
    'recommended' => 'Disarankan',
    'legacy' => 'Lawas',
];
