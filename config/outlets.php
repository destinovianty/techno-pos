<?php

// Nama kunci HARUS persis sama dengan kolom "outlet" di tabel users, products, dan orders.
//
// service   : persen service charge di kasir (0 = tidak ada)
// parkir    : true = outlet ini punya parkir (tombol cepat Mobil/Motor di kasir)
// terhubung : daftar outlet yang menunya bisa dijual dari kasir outlet ini (satu pembayaran)
return [
    'list' => [
        'Kals Maison' => ['label' => 'Resto western',              'type' => 'resto',    'color' => '#8B1E3F', 'tables' => 10, 'kitchen' => true,  'barcode' => false, 'service' => 5, 'parkir' => true, 'terhubung' => ['Needs Mart']],
        'Sky Watch'   => ['label' => 'Resto asia',                 'type' => 'resto',    'color' => '#0F766E', 'tables' => 10, 'kitchen' => true,  'barcode' => false, 'service' => 5, 'parkir' => true, 'terhubung' => []],
        'Needs Mart'  => ['label' => 'Mini mart',                  'type' => 'minimart', 'color' => '#2F7D32', 'tables' => 0,  'kitchen' => false, 'barcode' => true,  'service' => 0, 'parkir' => true, 'terhubung' => ['Kals Maison']],
        'Billiard'    => ['label' => 'Billiard, aksesoris, makan', 'type' => 'billiard', 'color' => '#5B2A86', 'tables' => 8,  'kitchen' => true,  'barcode' => false, 'service' => 5, 'parkir' => true, 'terhubung' => []],
        'GPM PTDI'    => ['label' => 'Cafe',                       'type' => 'cafe',     'color' => '#7A4A2B', 'tables' => 10, 'kitchen' => true,  'barcode' => false, 'service' => 5, 'parkir' => true, 'terhubung' => []],
        'Technocoffe' => ['label' => 'Makanan dan minuman',        'type' => 'fnb',      'color' => '#D9480F', 'tables' => 10, 'kitchen' => true,  'barcode' => false, 'service' => 5, 'parkir' => true, 'terhubung' => []],
    ],

    // Dipakai kalau outlet akun tidak ditemukan di daftar di atas.
    'default' => ['label' => '', 'type' => 'resto', 'color' => '#D9480F', 'tables' => 10, 'kitchen' => true, 'barcode' => false, 'service' => 0, 'parkir' => false, 'terhubung' => []],

    // Tarif parkir. Dipakai saat ParkirSeeder dijalankan. Setelah itu tarif bisa diubah PIC di tab Produk & Harga.
    'parkir_tarif' => ['Mobil' => 5000, 'Motor' => 3000],

    // Kategori yang TIDAK kena diskon dan service charge.
    'tanpa_diskon_service' => ['Parkir', 'Paket Jam', 'Aksesoris'],

    // Batas diskon persen yang boleh diisi kasir.
    'diskon_maks_persen' => 50,

    // Alamat dasar untuk QR meja. Isi lewat .env (QR_BASE_URL), misalnya http://192.168.1.10:8000
    // Kosong = memakai alamat yang sedang dibuka di browser kasir.
    'qr_base_url' => env('QR_BASE_URL'),
];
