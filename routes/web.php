<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\AdminAchievementController;
use App\Http\Controllers\Admin\AdminAboutController;
use App\Http\Controllers\Admin\AdminCollectionController;
use App\Http\Controllers\Admin\AdminDailyActivityController;
use App\Http\Controllers\Admin\AdminFacilityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ChatbotController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

// Halaman publik statis: Cache-Control edge-friendly untuk Vercel Edge
Route::middleware(\App\Http\Middleware\CachePublic::class)->group(function () {

    Route::get('/', function () {
        // Redirect admin domain root ke dashboard admin
        $host = request()->getHost();
        if (in_array($host, ['admin4putra.vercel.app'])) {
            return redirect('/admin');
        }

        // Pool koleksi di-cache 1 jam (tanpa acak di SQL); 8 pilihan acak diambil
        // per request di PHP agar tiap load tetap berbeda tanpa query TiDB.
        $pool = \Illuminate\Support\Facades\Cache::remember('public.carousel_pool', 60 * 60, function () {
            return \App\Models\Collection::select('name', 'name_en', 'image_path')
                ->whereNotNull('image_path')
                ->get()
                ->all();
        });

        $carouselCollections = collect();

        if (! empty($pool)) {
            $carouselCollections = collect($pool)->random(min(8, count($pool)))->map(function ($item) {
                $imgUrl = str_starts_with($item->image_path, 'http')
                    ? str_replace('/upload/', '/upload/w_400,c_fill,q_auto,f_auto/', $item->image_path)
                    : asset('storage/collections/'.$item->image_path);

                return [
                    'title' => app()->getLocale() === 'en' && $item->name_en ? $item->name_en : $item->name,
                    'image' => $imgUrl,
                ];
            });
        }

        return view('index', compact('carouselCollections'));
    });

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections');
    Route::get('/achievements', [AchievementController::class, 'publicIndex'])->name('achievements');

    Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities');

    Route::get('/daily-activities', [DailyActivityController::class, 'index'])->name('daily.activities');

    Route::get('/about', function () {
        $aboutPage = \App\Models\AboutPage::current();
        $leaderships = \Illuminate\Support\Facades\Cache::remember('about.leaderships', 300, function () {
            return \App\Models\Leadership::orderBy('sort_order')->get();
        });

        return view('about', compact('aboutPage', 'leaderships'));
    })->name('about');
});

Route::get('/contact', fn () => redirect('/about#contact'))->name('contact');

// Midtrans webhook callback (tanpa auth, dipanggil oleh Midtrans server)
Route::post('/midtrans/callback', [ChatbotController::class, 'midtransCallback'])->name('midtrans.callback');
Route::post('/midtrans/status', [ChatbotController::class, 'midtransCheckStatus'])->name('midtrans.status');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        Session::put('locale', $locale);
    }

    // Cache-buster ?v=: pastikan target redirect tidak dilayani cache HTTP/edge/
    // prefetch berbahasa lama. Cookie 'locale' mengaktifkan Vary: Cookie di CachePublic.
    $target = trim(preg_replace('/([?&])v=\d+/', '$1', url()->previous() ?: url('/')), '?&');
    if (!str_contains($target, url('/')) || str_contains($target, '/lang/') || str_contains($target, '/midtrans')) {
        $target = url('/');
    }
    $sep = str_contains($target, '?') ? '&' : '?';

    // no-store: redirect switch tidak boleh di-cache browser/edge
    return redirect()->to($target.$sep.'v='.time())
        ->withCookie(cookie('locale', $locale ?: 'id', 60 * 24 * 30))
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('lang.switch');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
});

// Midtrans callback (public, tanpa auth — dipanggil oleh server Midtrans)
Route::post('/midtrans/callback-laravel', [ChatbotController::class, 'midtransCallback'])->name('midtrans.callback.laravel');
Route::get('/midtrans/test/{order_id}', [ChatbotController::class, 'midtransTestApi'])->name('midtrans.test');

// Grup Rute Dashboard Admin PT 4Putra Vertex Aviary
Route::prefix('admin')->middleware(['admin.auth', 'admin.domain'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Logout dari admin domain
    Route::post('/logout', [LogoutController::class, 'logout'])->name('admin.logout');

    // Ping session (keep-alive + refresh CSRF token)
    Route::match(['get', 'post'], '/ping', [DashboardController::class, 'ping'])->name('admin.ping');

    // Profil akun
    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');

    // Chat API
    Route::get('/chat/contacts', [ChatController::class, 'contacts'])->name('admin.chat.contacts');
    Route::get('/chat/users', [ChatController::class, 'users'])->name('admin.chat.users');

    // Hapus gambar achievement
    Route::delete('/achievements/images/{image}', [AchievementController::class, 'destroyImage'])
        ->name('admin.achievements.images.destroy');

    // CRUD Achievements
    Route::resource('achievements', AdminAchievementController::class)->names([
        'index' => 'admin.achievements.index',
        'create' => 'admin.achievements.create',
        'store' => 'admin.achievements.store',
        'edit' => 'admin.achievements.edit',
        'update' => 'admin.achievements.update',
        'destroy' => 'admin.achievements.destroy',
    ]);

    // CRUD About Us (media hero + leadership) - satu halaman dengan Achievements
    Route::post('/about/media', [AdminAboutController::class, 'updateMedia'])->name('admin.about.media.update');
    Route::post('/about/leaderships', [AdminAboutController::class, 'storeLeader'])->name('admin.about.leadership.store');
    Route::put('/about/leaderships/{leadership}', [AdminAboutController::class, 'updateLeader'])->name('admin.about.leadership.update');
    Route::delete('/about/leaderships/{leadership}', [AdminAboutController::class, 'destroyLeader'])->name('admin.about.leadership.destroy');

    // CRUD Collections
    Route::resource('collections', AdminCollectionController::class)->names([
        'index' => 'admin.collections.index',
        'create' => 'admin.collections.create',
        'store' => 'admin.collections.store',
        'edit' => 'admin.collections.edit',
        'update' => 'admin.collections.update',
        'destroy' => 'admin.collections.destroy',
    ]);

    // CRUD Aktivitas Harian
    Route::resource('daily-activities', AdminDailyActivityController::class)->only([
        'index', 'store', 'update', 'destroy',
    ])->names([
        'index' => 'admin.daily-activities.index',
        'store' => 'admin.daily-activities.store',
        'update' => 'admin.daily-activities.update',
        'destroy' => 'admin.daily-activities.destroy',
    ]);

    // CRUD Fasilitas
    Route::resource('facilities', AdminFacilityController::class)->only([
        'index', 'store', 'update', 'destroy',
    ])->names([
        'index' => 'admin.facilities.index',
        'store' => 'admin.facilities.store',
        'update' => 'admin.facilities.update',
        'destroy' => 'admin.facilities.destroy',
    ]);

    // Manajemen User (hanya admin)
    Route::middleware('admin.only')->group(function () {
        Route::resource('users', AdminUserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);

        Route::get('/users/{user}/activity', [AdminUserController::class, 'activity'])->name('admin.users.activity');
        Route::post('/activity/{activity}/comment', [AdminUserController::class, 'commentActivity'])->name('admin.activity.comment');
    });

    // Chatbot WhatsApp (hanya admin)
    Route::middleware('admin.only')->prefix('chatbot')->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('admin.chatbot.index');
        Route::get('/inventaris', [ChatbotController::class, 'inventaris'])->name('admin.chatbot.inventaris');
        Route::post('/inventaris', [ChatbotController::class, 'inventarisStore'])->name('admin.chatbot.inventaris.store');
        Route::put('/inventaris/{inventari}', [ChatbotController::class, 'inventarisUpdate'])->name('admin.chatbot.inventaris.update');
        Route::delete('/inventaris/{inventari}', [ChatbotController::class, 'inventarisDestroy'])->name('admin.chatbot.inventaris.destroy');
        Route::get('/transaksi', [ChatbotController::class, 'transaksi'])->name('admin.chatbot.transaksi');
        Route::get('/transaksi/export/excel', [ChatbotController::class, 'transaksiExportExcel'])->name('admin.chatbot.transaksi.export.excel');
        Route::get('/transaksi/export/pdf', [ChatbotController::class, 'transaksiExportPdf'])->name('admin.chatbot.transaksi.export.pdf');
        Route::get('/transaksi/{transaksi}/invoice', [ChatbotController::class, 'transaksiInvoicePdf'])->name('admin.chatbot.transaksi.invoice');
        Route::post('/transaksi/{transaksi}/update-status', [ChatbotController::class, 'transaksiUpdateStatus'])->name('admin.chatbot.transaksi.update-status');
        Route::post('/transaksi/{transaksi}/force-paid', [ChatbotController::class, 'transaksiForcePaid'])->name('admin.chatbot.transaksi.force-paid');
        Route::post('/transaksi/status', [ChatbotController::class, 'transaksiStatusPolling'])->name('admin.chatbot.transaksi.status');
        Route::get('/percakapan/{pelanggan}', [ChatbotController::class, 'percakapan'])->name('admin.chatbot.percakapan');
        Route::post('/notifikasi/{notifikasi}/baca', [ChatbotController::class, 'notifikasiBaca'])->name('admin.chatbot.notifikasi.baca');

        // WhatsApp Chat (Bot ↔ Human)
        Route::get('/chat', [ChatbotController::class, 'chat'])->name('admin.chatbot.chat');
        Route::get('/chat/unread-messages', [ChatbotController::class, 'unreadMessages'])->name('admin.chatbot.chat.unread');
        Route::get('/chat/{pelanggan}/messages', [ChatbotController::class, 'chatMessages'])->name('admin.chatbot.chat.messages');
        Route::post('/chat/{pelanggan}/mark-read', [ChatbotController::class, 'markAsRead'])->name('admin.chatbot.chat.mark-read');
        Route::post('/chat/send', [ChatbotController::class, 'chatSend'])->name('admin.chatbot.chat.send');
        Route::post('/chat/toggle', [ChatbotController::class, 'chatToggle'])->name('admin.chatbot.chat.toggle');
        Route::delete('/chat/{pelanggan}/clear', [ChatbotController::class, 'chatClear'])->name('admin.chatbot.chat.clear');
        Route::post('/chat/{pelanggan}/delete', [ChatbotController::class, 'chatDelete'])->name('admin.chatbot.chat.delete');
    });
});
