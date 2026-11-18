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
     */
    'menus' => [
        'collections' => [
            'label' => 'Koleksi (Collections)',
            'patterns' => ['admin.collections.*'],
        ],
        'facilities' => [
            'label' => 'Fasilitas (Facilities)',
            'patterns' => ['admin.facilities.*'],
        ],
        'daily-activities' => [
            'label' => 'Aktivitas Harian (Daily Activities)',
            'patterns' => ['admin.daily-activities.*'],
        ],
        'achievements' => [
            'label' => 'Pencapaian (Achievements)',
            // About Us satu halaman dengan Achievements (tab sama di sidebar)
            'patterns' => ['admin.achievements.*', 'admin.about.*'],
        ],
        'aviary' => [
            'label' => 'Aviary',
            'patterns' => ['admin.aviary.*'],
        ],
        'kandang' => [
            'label' => 'Kandang',
            // Penempatan burung memakai nama route admin.burung.* (bukan admin.kandang.*)
            'patterns' => ['admin.kandang.*', 'admin.burung.*'],
        ],
        'vaksinasi' => [
            'label' => 'Vaksinasi',
            'patterns' => ['admin.vaksinasi.*'],
        ],
        'telur' => [
            'label' => 'Telur & Incubator',
            'patterns' => ['admin.telur.*'],
        ],
        'stok' => [
            'label' => 'Stok Makanan',
            'patterns' => ['admin.stok.*'],
        ],
        'keuangan' => [
            'label' => 'Keuangan',
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
