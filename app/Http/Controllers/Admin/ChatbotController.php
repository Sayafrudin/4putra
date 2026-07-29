<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventarisBurung;
use App\Models\NotifikasiAdmin;
use App\Models\Pelanggan;
use App\Models\Percakapan;
use App\Models\TransaksiChatbot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    // Dashboard chatbot
    public function index()
    {
        $totalPelanggan = Cache::remember('chatbot.totalPelanggan', 60, fn () => Pelanggan::count());
        $pelangganAktif = Cache::remember('chatbot.pelangganAktif', 60, fn () => Pelanggan::where('sesi_aktif', '!=', 'menu')->count());
        $percakapanHariIni = Cache::remember('chatbot.percakapanHariIni', 60, fn () => Percakapan::whereDate('created_at', today())->count());
        $notifikasiBelum = Cache::remember('chatbot.notifikasiBelum', 30, fn () => NotifikasiAdmin::belumDibaca()->count());
        $transaksiPending = Cache::remember('chatbot.transaksiPending', 60, fn () => TransaksiChatbot::where('status', 'pending')->count());
        $transaksiPaid = Cache::remember('chatbot.transaksiPaid', 60, fn () => TransaksiChatbot::where('status', 'paid')->count());

        $notifikasiTerbaru = Cache::remember('chatbot.notifikasiTerbaru', 30, function () {
            return NotifikasiAdmin::with('pelanggan:id,nomor_wa,nama')
                ->select('id', 'tipe', 'judul', 'isi', 'pelanggan_id', 'dibaca', 'created_at')
                ->latest()
                ->limit(20)
                ->get();
        });

        return view('admin.chatbot.index', compact(
            'totalPelanggan',
            'pelangganAktif',
            'percakapanHariIni',
            'notifikasiBelum',
            'transaksiPending',
            'transaksiPaid',
            'notifikasiTerbaru',
        ));
    }

    // Halaman WhatsApp Chat (admin bisa balas pelanggan)
    public function chat(Request $request)
    {
        $pelangganList = Pelanggan::select('id', 'nomor_wa', 'nama', 'sesi_aktif', 'pesan_terakhir')
            ->withCount('percakapan as total_chat')
            ->orderByDesc('pesan_terakhir')
            ->get();

        $selectedPelanggan = null;
        $riwayat = collect();

        if ($request->has('pelanggan_id')) {
            $selectedPelanggan = Pelanggan::select('id', 'nomor_wa', 'nama', 'sesi_aktif')->find($request->pelanggan_id);
            if ($selectedPelanggan) {
                $riwayat = $selectedPelanggan->percakapan()
                    ->select('id', 'pelanggan_id', 'pesan_pengirim', 'pesan_balasan', 'sumber_balasan', 'terkirim', 'created_at')
                    ->orderBy('created_at', 'asc')
                    ->limit(200)
                    ->get();
            }
        }

        return view('admin.chatbot.chat', compact('pelangganList', 'selectedPelanggan', 'riwayat'));
    }

    // Ambil pesan baru (polling)
    public function chatMessages(Request $request, Pelanggan $pelanggan)
    {
        $afterId = $request->input('after_id', 0);

        $messages = Percakapan::select('id', 'pelanggan_id', 'pesan_pengirim', 'pesan_balasan', 'sumber_balasan', 'terkirim', 'created_at')
            ->where('pelanggan_id', $pelanggan->id)
            ->where('id', '>', $afterId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Ambil pelanggan yang punya pesan belum dibaca admin
    public function unreadMessages()
    {
        $unread = \App\Models\Percakapan::select('pelanggan_id', DB::raw('COUNT(*) as jumlah'), DB::raw('MAX(id) as last_id'))
            ->where('dibaca_admin', false)
            ->whereNotNull('pesan_pengirim')
            ->groupBy('pelanggan_id')
            ->get()
            ->map(function ($item) {
                $pelanggan = Pelanggan::find($item->pelanggan_id);
                return [
                    'pelanggan_id' => $item->pelanggan_id,
                    'nama' => $pelanggan?->nama ?? 'Unknown',
                    'nomor_wa' => $pelanggan?->nomor_wa ?? '',
                    'jumlah' => $item->jumlah,
                    'last_id' => $item->last_id,
                ];
            });

        return response()->json($unread);
    }

    // Tandai pesan pelanggan sebagai sudah dibaca
    public function markAsRead(Request $request, Pelanggan $pelanggan)
    {
        \App\Models\Percakapan::where('pelanggan_id', $pelanggan->id)
            ->where('dibaca_admin', false)
            ->whereNotNull('pesan_pengirim')
            ->update(['dibaca_admin' => true]);

        return response()->json(['status' => 'OK']);
    }

    // Admin kirim pesan ke pelanggan
    public function chatSend(Request $request)
    {
        $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'pesan' => 'required|string|max:1000',
        ]);

        $pelanggan = Pelanggan::findOrFail($request->pelanggan_id);
        $pesan = $request->pesan;

        // Simpan ke database terlebih dahulu
        $chatBaru = Percakapan::create([
            'pelanggan_id' => $pelanggan->id,
            'pesan_pengirim' => null,
            'pesan_balasan' => $pesan,
            'sumber_balasan' => 'admin',
            'terkirim' => false, // default belum terkirim
        ]);

        $pelanggan->update(['pesan_terakhir' => now()]);

        // Kirim via Baileys API (whatsapp.js)
        $note = '';
        $sent = false;
        $baileysUrl = env('BAILEYS_BOT_URL', 'http://127.0.0.1:3001');
        $metaApiUrl = env('META_API_URL', 'http://127.0.0.1:3000');

        try {
            $response = Http::timeout(5)->post($baileysUrl.'/send', [
                'jid' => $pelanggan->nomor_wa,
                'pesan' => $pesan,
            ]);

            if ($response->successful() && $response->json('status') === 'OK') {
                $sent = true;
                $note = 'Pesan terkirim via WhatsApp';
            }
        } catch (\Exception $e) {
            // Baileys offline
        }

        // Fallback: coba via index.js (Meta API) jika Baileys gagal dan bukan @lid
        if (! $sent && ! str_contains($pelanggan->nomor_wa, '@lid')) {
            try {
                $response = Http::timeout(5)->post($metaApiUrl.'/api/chat/send', [
                    'pelanggan_id' => $pelanggan->id,
                    'pesan' => $pesan,
                ]);

                if ($response->successful()) {
                    $sent = true;
                    $note = 'Pesan terkirim via Meta API';
                }
            } catch (\Exception $e) {
                // Meta API offline
            }
        }

        if (! $sent) {
            $note = 'Pesan disimpan (WhatsApp offline)';
        } else {
            // Update status terkirim
            $chatBaru->update(['terkirim' => true]);
        }

        return response()->json([
            'status' => 'OK',
            'message_id' => $chatBaru->id,
            'sent' => $sent,
            'note' => $note,
        ]);
    }

    // Toggle mode bot/human
    public function chatToggle(Request $request)
    {
        $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'mode' => 'required|in:ai,human,menu',
        ]);

        $pelanggan = Pelanggan::findOrFail($request->pelanggan_id);
        $mode = $request->mode;

        $pelanggan->update(['sesi_aktif' => $mode]);

        // Kirim notifikasi ke pelanggan via Baileys API
        $pesanNotif = '';
        if ($mode === 'human') {
            $pesanNotif = 'Halo, Kak. Percakapan ini sekarang diambil alih oleh admin 4PUTRA, ya. Mohon tunggu sebentar, kami akan segera membalas pesan Kakak.';
        } else {
            $pesanNotif = 'Terima kasih atas waktunya, Kak. Percakapan ini sekarang kami alihkan kembali ke mode sistem. Jika masih ada yang ingin diketahui tentang perawatan atau pemesanan parrot di 4PUTRA, silakan langsung ditanyakan.';
        }

        if ($pesanNotif) {
            // Simpan notifikasi ke database
            Percakapan::create([
                'pelanggan_id' => $pelanggan->id,
                'pesan_pengirim' => null,
                'pesan_balasan' => $pesanNotif,
                'sumber_balasan' => 'system',
            ]);

            // Kirim via Baileys API
            try {
                Http::timeout(5)->post(env('BAILEYS_BOT_URL', 'http://127.0.0.1:3001').'/send', [
                    'jid' => $pelanggan->nomor_wa,
                    'pesan' => $pesanNotif,
                ]);
            } catch (\Exception $e) {
                // Baileys offline — notifikasi sudah tersimpan di DB
            }
        }

        return response()->json([
            'status' => 'OK',
            'new_mode' => $mode,
        ]);
    }

    // Daftar inventaris burung
    public function inventaris()
    {
        $inventaris = InventarisBurung::orderBy('nama_spesies')->orderBy('fase')->get();

        return view('admin.chatbot.inventaris.index', compact('inventaris'));
    }

    // Tambah inventaris
    public function inventarisStore(Request $request)
    {
        $validated = $request->validate([
            'nama_spesies' => 'required|string|max:100',
            'fase' => 'required|in:anakan,dewasa',
            'harga' => 'required|numeric|min:1',
            'stok' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
        ]);

        InventarisBurung::create($validated);

        Cache::forget('chatbot.totalPelanggan');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Inventaris berhasil ditambahkan!']);
        }

        return redirect()->route('admin.chatbot.inventaris')->with('success', 'Inventaris berhasil ditambahkan!');
    }

    // Update inventaris
    public function inventarisUpdate(Request $request, InventarisBurung $inventari)
    {
        $validated = $request->validate([
            'nama_spesies' => 'required|string|max:100',
            'fase' => 'required|in:anakan,dewasa',
            'harga' => 'required|numeric|min:1',
            'stok' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'aktif' => 'boolean',
        ]);

        $inventari->update($validated);

        Cache::forget('chatbot.totalPelanggan');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Inventaris berhasil diperbarui!']);
        }

        return redirect()->route('admin.chatbot.inventaris')->with('success', 'Inventaris berhasil diperbarui!');
    }

    // Hapus inventaris
    public function inventarisDestroy(Request $request, InventarisBurung $inventari)
    {
        $inventari->delete();

        Cache::forget('chatbot.totalPelanggan');

        if ($request->expectsJson()) {
            return response()->json(['status' => 'OK', 'message' => 'Inventaris berhasil dihapus!']);
        }

        return redirect()->route('admin.chatbot.inventaris')->with('success', 'Inventaris berhasil dihapus!');
    }

    // Daftar transaksi
    public function transaksi()
    {
        $transaksi = TransaksiChatbot::with(['pelanggan:id,nomor_wa,nama', 'inventaris:id,nama_spesies,fase,harga', 'pembayaran:id,transaksi_id,nominal,status'])
            ->select('id', 'pelanggan_id', 'inventaris_id', 'nominal_dp', 'total_harga', 'quantity', 'status', 'midtrans_order_id', 'created_at')
            ->latest()
            ->paginate(20);

        // Hitung statistik (cached)
        $totalPaid = Cache::remember('chatbot.transaksi.paid', 60, fn () => TransaksiChatbot::where('status', 'paid')->count());
        $totalPending = Cache::remember('chatbot.transaksi.pending', 60, fn () => TransaksiChatbot::where('status', 'pending')->count());
        $totalRevenue = Cache::remember('chatbot.transaksi.revenue', 60, fn () => TransaksiChatbot::where('status', 'paid')->sum('total_harga'));

        return view('admin.chatbot.transaksi.index', compact('transaksi', 'totalPaid', 'totalPending', 'totalRevenue'));
    }

    // Detail percakapan pelanggan (read-only view)
    public function percakapan(Pelanggan $pelanggan)
    {
        $riwayat = $pelanggan->percakapan()
            ->select('id', 'pelanggan_id', 'pesan_pengirim', 'pesan_balasan', 'sumber_balasan', 'terkirim', 'created_at')
            ->latest()
            ->paginate(50);

        return view('admin.chatbot.percakapan', compact('pelanggan', 'riwayat'));
    }

    // Tandai notifikasi sudah dibaca
    public function notifikasiBaca(NotifikasiAdmin $notifikasi)
    {
        $notifikasi->update(['dibaca' => true]);

        Cache::forget('chatbot.notifikasiBelum');
        Cache::forget('chatbot.notifikasiTerbaru');

        return response()->json(['status' => 'OK']);
    }

    // Hapus semua percakapan pelanggan
    public function chatClear(Pelanggan $pelanggan)
    {
        $pelanggan->percakapan()->delete();

        return response()->json([
            'status' => 'OK',
            'message' => 'Semua percakapan berhasil dihapus.',
        ]);
    }

    // ============================================================
    // MIDTRANS CALLBACK & STATUS CHECK
    // ============================================================

    // Webhook callback dari Midtrans (dipanggil server-to-server)
    public function midtransCallback(Request $request)
    {
        try {
            $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $signatureKey = $request->input('signature_key');
            $transactionStatus = $request->input('transaction_status');
            $transactionId = $request->input('transaction_id');
            $paymentType = $request->input('payment_type');

            // Verifikasi signature
            $input = $orderId.$statusCode.$grossAmount.$serverKey;
            $computed = hash('sha512', $input);

            if ($computed !== $signatureKey) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $transaksi = TransaksiChatbot::where('midtrans_order_id', $orderId)->first();
            if (! $transaksi) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Tentukan status
            $statusBaru = 'pending';
            if (in_array($transactionStatus, ['settlement', 'capture'])) {
                $statusBaru = 'paid';
            } elseif ($transactionStatus === 'expire') {
                $statusBaru = 'expired';
            } elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
                $statusBaru = 'cancelled';
            }

            // Simpan data pembayaran
            $transaksi->pembayaran()->create([
                'midtrans_txn_id' => $transactionId,
                'metode' => $paymentType,
                'nominal' => $grossAmount,
                'status' => $transactionStatus,
                'raw_webhook' => $request->all(),
            ]);

            // Update status transaksi
            $transaksi->update(['status' => $statusBaru]);

            // Invalidate cache transaksi
            Cache::forget('chatbot.transaksiPending');
            Cache::forget('chatbot.transaksiPaid');
            Cache::forget('chatbot.transaksi.pending');
            Cache::forget('chatbot.transaksi.paid');
            Cache::forget('chatbot.transaksi.revenue');

            // Jika berhasil, kirim notifikasi ke admin + WA ke pelanggan
            if ($statusBaru === 'paid') {
                $pelanggan = $transaksi->pelanggan;
                NotifikasiAdmin::create([
                    'tipe' => 'pembayaran',
                    'judul' => 'Pembayaran Diterima!',
                    'isi' => "Pembayaran dari {$pelanggan->nama} sebesar Rp ".number_format($grossAmount, 0, ',', '.').' telah berhasil.',
                    'pelanggan_id' => $transaksi->pelanggan_id,
                    'dibaca' => false,
                ]);

                Cache::forget('chatbot.notifikasiBelum');
                Cache::forget('chatbot.notifikasiTerbaru');

                // Kirim WA ke pelanggan via Baileys
                $this->kirimNotifPembayaranSukses($transaksi, $pelanggan, $grossAmount, $paymentType);
            }

            return response()->json(['status' => 'OK']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Cek status transaksi dari Midtrans (dipanggil admin via AJAX)
    public function midtransCheckStatus(Request $request)
    {
        $request->validate(['order_id' => 'required|string']);

        try {
            $serverKey = env('MIDTRANS_SERVER_KEY');
            $auth = base64_encode($serverKey.':');

            $response = Http::timeout(15)->withoutVerifying()->withHeaders([
                'Authorization' => "Basic $auth",
                'Accept' => 'application/json',
            ])->get("https://app.sandbox.midtrans.com/v2/{$request->order_id}/status");

            if ($response->successful()) {
                $data = $response->json();
                $transactionStatus = $data['transaction_status'] ?? 'unknown';
                $paymentType = $data['payment_type'] ?? '-';

                $statusBaru = 'pending';
                if (in_array($transactionStatus, ['settlement', 'capture'])) {
                    $statusBaru = 'paid';
                } elseif ($transactionStatus === 'expire') {
                    $statusBaru = 'expired';
                } elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
                    $statusBaru = 'cancelled';
                }

                $transaksi = TransaksiChatbot::where('midtrans_order_id', $request->order_id)->first();
                if ($transaksi && $transaksi->status !== $statusBaru) {
                    $transaksi->update(['status' => $statusBaru]);

                    if ($statusBaru === 'paid') {
                        $transaksi->pembayaran()->create([
                            'midtrans_txn_id' => $data['transaction_id'] ?? '-',
                            'metode' => $paymentType,
                            'nominal' => $data['gross_amount'] ?? 0,
                            'status' => $transactionStatus,
                            'raw_webhook' => $data,
                        ]);

                        // Kirim notifikasi admin + WA ke pelanggan
                        $pelanggan = $transaksi->pelanggan;
                        NotifikasiAdmin::create([
                            'tipe' => 'pembayaran',
                            'judul' => 'Pembayaran Diterima!',
                            'isi' => "Pembayaran dari {$pelanggan->nama} sebesar Rp ".number_format($data['gross_amount'] ?? 0, 0, ',', '.').' telah berhasil.',
                            'pelanggan_id' => $transaksi->pelanggan_id,
                            'dibaca' => false,
                        ]);

                        $this->kirimNotifPembayaranSukses($transaksi, $pelanggan, $data['gross_amount'] ?? 0, $paymentType);
                    }
                }

                return response()->json([
                    'status' => 'OK',
                    'transaction_status' => $transactionStatus,
                    'mapped_status' => $statusBaru,
                    'payment_type' => $paymentType,
                ]);
            }

            return response()->json(['status' => 'ERROR', 'message' => 'Gagal cek status Midtrans']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()]);
        }
    }

    // Polling status semua transaksi pending (dipanggil AJAX dari halaman transaksi)
    public function transaksiStatusPolling()
    {
        $pendingCount = TransaksiChatbot::where('status', 'pending')
            ->whereNotNull('midtrans_order_id')
            ->count();

        if ($pendingCount === 0) {
            return response()->json(['status' => 'OK', 'updated' => 0, 'message' => 'Tidak ada transaksi pending.']);
        }

        $pendingTransaksis = TransaksiChatbot::select('id', 'pelanggan_id', 'total_harga', 'status', 'midtrans_order_id')
            ->where('status', 'pending')
            ->whereNotNull('midtrans_order_id')
            ->cursor();

        if ($pendingTransaksis->isEmpty()) {
            return response()->json(['status' => 'OK', 'updated' => 0, 'message' => 'Tidak ada transaksi pending.']);
        }

        $updated = 0;
        $errors = [];
        $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $auth = base64_encode($serverKey.':');

        foreach ($pendingTransaksis as $trx) {
            try {
                $url = "https://app.sandbox.midtrans.com/v2/{$trx->midtrans_order_id}/status";
                $response = Http::timeout(15)->withoutVerifying()->withHeaders([
                    'Authorization' => "Basic $auth",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $transactionStatus = $data['transaction_status'] ?? 'unknown';
                    $paymentType = $data['payment_type'] ?? '-';
                    $transactionId = $data['transaction_id'] ?? '-';
                    $grossAmount = $data['gross_amount'] ?? 0;

                    $statusBaru = 'pending';
                    if (in_array($transactionStatus, ['settlement', 'capture'])) {
                        $statusBaru = 'paid';
                    } elseif ($transactionStatus === 'expire') {
                        $statusBaru = 'expired';
                    } elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
                        $statusBaru = 'cancelled';
                    }

                    if ($statusBaru !== 'pending') {
                        $trx->update(['status' => $statusBaru]);

                        // Simpan data pembayaran jika belum ada
                        $sudahAda = $trx->pembayaran()->where('midtrans_txn_id', $transactionId)->exists();
                        if (! $sudahAda) {
                            $trx->pembayaran()->create([
                                'midtrans_txn_id' => $transactionId,
                                'metode' => $paymentType,
                                'nominal' => $grossAmount,
                                'status' => $transactionStatus,
                                'raw_webhook' => $data,
                            ]);
                        }

                        // Kirim notifikasi admin + WA ke pelanggan jika paid
                        if ($statusBaru === 'paid') {
                            $pelanggan = $trx->pelanggan;

                            // Notifikasi admin di dashboard
                            NotifikasiAdmin::create([
                                'tipe' => 'pembayaran',
                                'judul' => 'Pembayaran Diterima!',
                                'isi' => "Pembayaran dari {$pelanggan->nama} sebesar Rp ".number_format($grossAmount, 0, ',', '.').' telah berhasil.',
                                'pelanggan_id' => $trx->pelanggan_id,
                                'dibaca' => false,
                            ]);

                            // Kirim pesan WhatsApp ke pelanggan via Baileys
                            $this->kirimNotifPembayaranSukses($trx, $pelanggan, $grossAmount, $paymentType);
                        }

                        $updated++;
                    }
                } else {
                    $errors[] = "{$trx->midtrans_order_id}: HTTP {$response->status()}";
                    \Log::warning('Midtrans API response error: '.$trx->midtrans_order_id.' - HTTP '.$response->status().' - Body: '.$response->body());
                }
            } catch (\Exception $e) {
                $errors[] = "{$trx->midtrans_order_id}: {$e->getMessage()}";
                \Log::error('Gagal cek status Midtrans: '.$trx->midtrans_order_id.' - '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'OK',
            'updated' => $updated,
            'total_pending' => $pendingCount,
            'errors' => $errors,
        ]);
    }

    // Kirim notifikasi pembayaran sukses ke pelanggan via WhatsApp
    private function kirimNotifPembayaranSukses($trx, $pelanggan, $grossAmount, $paymentType)
    {
        $pesan = "✅ *Pembayaran Berhasil!*\n\n".
                 "Order ID: {$trx->midtrans_order_id}\n".
                 'Nominal: Rp '.number_format($grossAmount, 0, ',', '.')."\n".
                 "Metode: {$paymentType}\n\n".
                 'Terima kasih telah berbelanja di PT 4Putra Vertex Aviary! Admin kami akan segera menghubungi Kakak untuk pengiriman.';

        $terkirim = false;

        // Kirim via Baileys API (whatsapp.js)
        try {
            $response = Http::timeout(5)->post(env('BAILEYS_BOT_URL', 'http://127.0.0.1:3001').'/send', [
                'jid' => $pelanggan->nomor_wa,
                'pesan' => $pesan,
            ]);

            if ($response->successful() && $response->json('status') === 'OK') {
                $terkirim = true;
                \Log::info("Notifikasi pembayaran sukses terkirim ke {$pelanggan->nomor_wa}");
            }
        } catch (\Exception $e) {
            \Log::warning("Gagal kirim notif WA ke {$pelanggan->nomor_wa}: ".$e->getMessage());

            // Fallback: coba via index.js (Meta API)
            try {
                $response = Http::timeout(5)->post(env('META_API_URL', 'http://127.0.0.1:3000').'/api/chat/send', [
                    'pelanggan_id' => $trx->pelanggan_id,
                    'pesan' => $pesan,
                ]);

                if ($response->successful()) {
                    $terkirim = true;
                }
            } catch (\Exception $e2) {
                // Kedua metode gagal
            }
        }

        // Simpan ke percakapan dengan status terkirim
        Percakapan::create([
            'pelanggan_id' => $trx->pelanggan_id,
            'pesan_pengirim' => null,
            'pesan_balasan' => $pesan,
            'sumber_balasan' => 'system',
            'terkirim' => $terkirim,
        ]);
    }

    // ============================================================
    // EXPORT EXCEL (CSV)
    // ============================================================
    public function transaksiExportExcel()
    {
        $csv = "Order ID,Pelanggan,Nomor WA,Spesies,Fase,Harga,Status,Tanggal\n";

        TransaksiChatbot::with(['pelanggan:id,nomor_wa,nama', 'inventaris:id,nama_spesies,fase', 'pembayaran:id,transaksi_id,nominal,status'])
            ->select('id', 'pelanggan_id', 'inventaris_id', 'total_harga', 'status', 'midtrans_order_id', 'created_at')
            ->latest()
            ->cursor()
            ->each(function ($trx) use (&$csv) {
                $nama = '"'.str_replace('"', '""', $trx->pelanggan->nama ?? '-').'"';
                $nomor = '"'.str_replace(['@s.whatsapp.net', '@lid'], '', $trx->pelanggan->nomor_wa ?? '').'"';
                $spesies = '"'.str_replace('"', '""', $trx->inventaris?->nama_spesies ?? '-').'"';
                $fase = $trx->inventaris?->fase === 'anakan' ? 'Baby' : ($trx->inventaris?->fase ?? '-');
                $harga = $trx->total_harga;
                $status = $trx->status;
                $tanggal = $trx->created_at->format('Y-m-d H:i:s');

                $csv .= "{$trx->midtrans_order_id},{$nama},{$nomor},{$spesies},{$fase},{$harga},{$status},{$tanggal}\n";
            });

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transaksi_chatbot_'.date('Y-m-d').'.csv"');
    }

    // ============================================================
    // EXPORT PDF (INVOICE)
    // ============================================================
    public function transaksiExportPdf()
    {
        $transaksi = TransaksiChatbot::with(['pelanggan:id,nomor_wa,nama', 'inventaris:id,nama_spesies,fase,harga', 'pembayaran:id,transaksi_id,nominal,status'])
            ->select('id', 'pelanggan_id', 'inventaris_id', 'nominal_dp', 'total_harga', 'quantity', 'status', 'midtrans_order_id', 'created_at')
            ->latest()
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.chatbot.transaksi.pdf', compact('transaksi'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('transaksi_chatbot_'.date('Y-m-d').'.pdf');
    }

    // ============================================================
    // INVOICE PDF PER TRANSAKSI
    // ============================================================
    public function transaksiInvoicePdf(TransaksiChatbot $transaksi)
    {
        $transaksi->load(['pelanggan', 'inventaris', 'pembayaran']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.chatbot.transaksi.invoice', compact('transaksi'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Invoice_'.$transaksi->midtrans_order_id.'.pdf');
    }

    // ============================================================
    // UPDATE STATUS MANUAL
    // ============================================================
    public function transaksiUpdateStatus(Request $request, TransaksiChatbot $transaksi)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,expired,cancelled',
        ]);

        $statusLama = $transaksi->status;
        $transaksi->update(['status' => $request->status]);

        Cache::forget('chatbot.transaksiPending');
        Cache::forget('chatbot.transaksiPaid');
        Cache::forget('chatbot.transaksi.pending');
        Cache::forget('chatbot.transaksi.paid');
        Cache::forget('chatbot.transaksi.revenue');

        // Jika diubah ke paid, kirim notifikasi WA ke pelanggan
        if ($request->status === 'paid' && $statusLama !== 'paid') {
            $pelanggan = $transaksi->pelanggan;
            if ($pelanggan) {
                $this->kirimNotifPembayaranSukses($transaksi, $pelanggan, $transaksi->total_harga, 'Manual');
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'OK',
                'message' => "Status transaksi berhasil diubah ke {$request->status}.",
            ]);
        }

        return redirect()->route('admin.chatbot.transaksi')->with('success', 'Status transaksi berhasil diperbarui!');
    }

    // ============================================================
    // FORCE PAID (ketika V2 API tidak bisa akses Snap transaction)
    // ============================================================
    public function transaksiForcePaid(Request $request, TransaksiChatbot $transaksi)
    {
        $request->validate([
            'payment_type' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        $statusLama = $transaksi->status;
        $transaksi->update(['status' => 'paid']);

        Cache::forget('chatbot.transaksiPending');
        Cache::forget('chatbot.transaksiPaid');
        Cache::forget('chatbot.transaksi.pending');
        Cache::forget('chatbot.transaksi.paid');
        Cache::forget('chatbot.transaksi.revenue');

        // Simpan data pembayaran manual
        $sudahAda = $transaksi->pembayaran()->where('status', 'settlement')->exists();
        if (! $sudahAda) {
            $transaksi->pembayaran()->create([
                'midtrans_txn_id' => $request->input('transaction_id', 'MANUAL-'.$transaksi->midtrans_order_id),
                'metode' => $request->input('payment_type', 'QRIS'),
                'nominal' => $transaksi->total_harga,
                'status' => 'settlement',
                'raw_webhook' => ['manual_update' => true, 'updated_at' => now()->toISOString()],
            ]);
        }

        // Kirim notifikasi WA ke pelanggan
        if ($statusLama !== 'paid') {
            $pelanggan = $transaksi->pelanggan;
            if ($pelanggan) {
                $this->kirimNotifPembayaranSukses($transaksi, $pelanggan, $transaksi->total_harga, $request->input('payment_type', 'QRIS'));
            }
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Status transaksi berhasil diubah ke PAID.',
        ]);
    }

    // ============================================================
    // TEST API MIDTRANS (debug)
    // ============================================================
    public function midtransTestApi($orderId)
    {
        $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $auth = base64_encode($serverKey.':');

        $trx = TransaksiChatbot::where('midtrans_order_id', $orderId)->first();

        $result = [
            'order_id' => $orderId,
            'db_status' => $trx?->status ?? 'Tidak ada di DB',
            'qr_url' => $trx?->qr_url ?? '-',
        ];

        // Cek via V2 API
        try {
            $url = "https://app.sandbox.midtrans.com/v2/{$orderId}/status";
            $response = Http::timeout(15)->withoutVerifying()->withHeaders([
                'Authorization' => "Basic $auth",
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $transactionStatus = $data['transaction_status'] ?? 'unknown';
                $result['midtrans_status'] = $transactionStatus;
                $result['payment_type'] = $data['payment_type'] ?? '-';
                $result['transaction_id'] = $data['transaction_id'] ?? '-';

                // Auto-update jika settlement/capture
                if (in_array($transactionStatus, ['settlement', 'capture']) && $trx && $trx->status !== 'paid') {
                    $trx->update(['status' => 'paid']);
                    $trx->pembayaran()->create([
                        'midtrans_txn_id' => $data['transaction_id'] ?? '-',
                        'metode' => $data['payment_type'] ?? '-',
                        'nominal' => $data['gross_amount'] ?? $trx->total_harga,
                        'status' => 'settlement',
                        'raw_webhook' => $data,
                    ]);
                    $result['auto_updated'] = 'Status diupdate ke PAID!';
                }
            } else {
                $result['v2_error'] = "HTTP {$response->status()} — Transaksi Snap tidak ditemukan di V2 API (normal di sandbox)";
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        // Jika user tambahkan ?force_paid=1, force update ke paid
        if (request('force_paid') && $trx && $trx->status !== 'paid') {
            $trx->update(['status' => 'paid']);
            $trx->pembayaran()->create([
                'midtrans_txn_id' => 'FORCE-'.$orderId,
                'metode' => 'QRIS',
                'nominal' => $trx->total_harga,
                'status' => 'settlement',
                'raw_webhook' => ['forced' => true],
            ]);

            // Kirim WA ke pelanggan
            $pelanggan = $trx->pelanggan;
            if ($pelanggan) {
                $this->kirimNotifPembayaranSukses($trx, $pelanggan, $trx->total_harga, 'QRIS');
            }

            $result['force_updated'] = 'Status FORCE diupdate ke PAID + WA terkirim!';
        }

        $result['tip'] = 'Tambah ?force_paid=1 di URL untuk force update ke paid';

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    }
}
