<?php

/**
 * Registry menu admin — SATU sumber kebenaran untuk sidebar, form hak akses,
 * dan middleware menu.access. Menambah menu baru = tambah entri di sini saja.
 *
 * 'patterns' memakai pola fnmatch pada NAMA ROUTE (bukan URL), mencakup semua
 * route pendukung halaman (export, AJAX, endpoint terkait) agar satu halaman
 * tidak terbelah blok-izinkan.
 */
return [
    /*
     * Menu yang bisa diatur per akun admin. Kunci = disimpan di users.menu_access.
     * 'akses' => 'admin' = menu eksklusif role admin (server digate admin.only);
     * 'akses' => 'semua' = role user juga bisa diberi menu ini (monitoring farm).
     */
    'menus' => [
        'collections' => [
            'label' => 'Koleksi (Collections)',
            'akses' => 'admin',
            'patterns' => ['admin.collections.*'],
        ],
        'facilities' => [
            'label' => 'Fasilitas (Facilities)',
            'akses' => 'admin',
            'patterns' => ['admin.facilities.*'],
        ],
        'daily-activities' => [
            'label' => 'Aktivitas Harian (Daily Activities)',
            'akses' => 'admin',
            'patterns' => ['admin.daily-activities.*'],
        ],
        'achievements' => [
            'label' => 'Pencapaian (Achievements)',
            'akses' => 'admin',
            // About Us satu halaman dengan Achievements (tab sama di sidebar)
            'patterns' => ['admin.achievements.*', 'admin.about.*'],
        ],
        'aviary' => [
            'label' => 'Aviary',
            'akses' => 'semua',
            'patterns' => ['admin.aviary.*'],
        ],
        'kandang' => [
            'label' => 'Kandang',
            'akses' => 'semua',
            // Penempatan burung memakai nama route admin.burung.* (bukan admin.kandang.*)
            'patterns' => ['admin.kandang.*', 'admin.burung.*'],
        ],
        'vaksinasi' => [
            'label' => 'Obat & Vaksinasi',
            'akses' => 'semua',
            'patterns' => ['admin.vaksinasi.*'],
        ],
        'telur' => [
            'label' => 'Telur & Incubator',
            'akses' => 'semua',
            'patterns' => ['admin.telur.*'],
        ],
        'penjualan-baby' => [
            'label' => 'Penjualan Baby',
            'akses' => 'semua',
            // Endpoint simpan jual tetap admin.baby.jual — terikat menu ini, bukan Baby.
            // Harus di atas 'baby' agar pencocokan pertama menang.
            'patterns' => ['admin.penjualan-baby.*', 'admin.baby.jual'],
        ],
        'baby' => [
            'label' => 'Baby',
            'akses' => 'semua',
            // Pindah baby ke kandang/aviary memakai endpoint admin.burung.* (sama dengan papan kandang)
            'patterns' => ['admin.baby.*', 'admin.burung.*'],
        ],
        'stok' => [
            'label' => 'Stok Makanan',
            'akses' => 'semua',
            'patterns' => ['admin.stok.*'],
        ],
        'keuangan' => [
            'label' => 'Keuangan',
            'akses' => 'semua',
            'patterns' => ['admin.keuangan.*'],
        ],
    ],

    /*
     * Route yang TIDAK terikat menu (semua yang lolos admin.auth boleh mengakses):
     * dashboard = halaman arahan login (wajib selalu hidup), profil, sesi, chat widget.
     */
    'always_allowed' => [
        'admin.dashboard',
        'admin.logout',
        'admin.ping',
        'admin.profile.*',
        'admin.chat.*',
        // Kelola Akun User & aktivitas: khusus admin penuh (menu_access NULL) —
        // ditangani khusus di MenuAccess, di sini hanya untuk test peta route.
        'admin.users.*',
        'admin.activity.*',
        // Chatbot WhatsApp: fitur skripsi local-only, tetap ikut role admin biasa
        'admin.chatbot.*',
    ],
];
