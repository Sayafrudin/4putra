<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaksi->midtrans_order_id }} — PT 4Putra Vertex Aviary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1a1a2e; font-size: 12px; line-height: 1.6; }

        .header { background: linear-gradient(135deg, #E62C37 0%, #c5242d 100%); color: white; padding: 30px 40px; }
        .header h1 { font-size: 24px; font-weight: 800; letter-spacing: 1px; }
        .header p { font-size: 11px; opacity: 0.9; margin-top: 4px; }
        .header .invoice-title { text-align: right; font-size: 20px; font-weight: 800; letter-spacing: 2px; }
        .header .invoice-date { text-align: right; font-size: 11px; opacity: 0.8; margin-top: 4px; }

        .content { padding: 30px 40px; }

        .info-grid { display: flex; gap: 30px; margin-bottom: 30px; }
        .info-box { flex: 1; background: #f8f9fa; border-radius: 8px; padding: 18px; border: 1px solid #e8e8e8; }
        .info-box .label { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 8px; }
        .info-box .value { font-size: 13px; font-weight: 600; color: #1a1a2e; }
        .info-box .value small { font-weight: 400; color: #888; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1a1a2e; color: white; padding: 12px 15px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:first-child { border-radius: 6px 0 0 0; }
        thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        tbody td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        tbody td:last-child { text-align: right; font-weight: 600; }

        .total-row { background: #f8f9fa; }
        .total-row td { font-weight: 700; font-size: 14px; border-top: 2px solid #1a1a2e; }

        .status { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-expired { background: #f3f4f6; color: #6b7280; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .payment-info { margin-top: 25px; background: #f0f4ff; border-radius: 8px; padding: 18px; border: 1px solid #d0d7ff; }
        .payment-info h3 { font-size: 12px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px; }
        .payment-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .payment-row .label { color: #666; }
        .payment-row .value { font-weight: 600; }

        .footer { padding: 20px 40px; border-top: 2px solid #E62C37; margin-top: 30px; }
        .footer p { font-size: 10px; color: #888; }
        .footer .company { font-weight: 700; color: #1a1a2e; font-size: 12px; }

        .stamp { text-align: right; margin-top: 30px; }
        .stamp .box { display: inline-block; border: 2px solid #16a34a; border-radius: 8px; padding: 8px 20px; color: #16a34a; font-weight: 800; font-size: 14px; letter-spacing: 2px; transform: rotate(-5deg); }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>PT 4PUTRA VERTEX AVIARY</h1>
                <p>Penangkaran Burung Paruh Bengkok Premium — Surabaya Barat</p>
            </div>
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-date">Tanggal: {{ $transaksi->created_at->format('d F Y') }}</div>
                <div class="invoice-date">No: {{ $transaksi->midtrans_order_id }}</div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-box">
                <div class="label">Pelanggan</div>
                <div class="value">{{ $transaksi->pelanggan->nama ?? '-' }}</div>
                <div style="font-size: 11px; color: #888; margin-top: 4px;">
                    {{ str_replace(['@s.whatsapp.net', '@lid'], '', $transaksi->pelanggan->nomor_wa ?? '') }}
                </div>
            </div>
            <div class="info-box">
                <div class="label">Status Pembayaran</div>
                <div class="value">
                    @if($transaksi->status === 'paid')
                        <span class="status status-paid">LUNAS</span>
                    @elseif($transaksi->status === 'pending')
                        <span class="status status-pending">PENDING</span>
                    @elseif($transaksi->status === 'expired')
                        <span class="status status-expired">KADALUARSA</span>
                    @else
                        <span class="status status-cancelled">DIBATALKAN</span>
                    @endif
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th>Fase</th>
                    <th>Harga Satuan</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: 700;">{{ $transaksi->inventaris?->nama_spesies ?? 'Produk' }}</div>
                        <div style="color: #888; font-size: 10px;">{{ $transaksi->inventaris?->deskripsi ?? '' }}</div>
                    </td>
                    <td>
                        @if($transaksi->inventaris)
                            <span style="display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600;
                                {{ $transaksi->inventaris->fase === 'anakan' ? 'background: #dbeafe; color: #1e40af;' : 'background: #dcfce7; color: #166534;' }}">
                                {{ $transaksi->inventaris->fase === 'anakan' ? 'Baby' : 'Dewasa' }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>Rp {{ number_format($transaksi->inventaris?->harga ?? $transaksi->total_harga, 0, ',', '.') }}</td>
                    <td style="text-align: center;">{{ $transaksi->quantity ?? 1 }}</td>
                    <td>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right; padding-right: 20px;">TOTAL</td>
                    <td style="font-size: 16px; color: #E62C37;">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        @if($transaksi->pembayaran->count() > 0)
            <div class="payment-info">
                <h3>Detail Pembayaran</h3>
                @foreach($transaksi->pembayaran as $bayar)
                    <div class="payment-row">
                        <span class="label">Metode</span>
                        <span class="value">{{ strtoupper($bayar->metode ?? '-') }}</span>
                    </div>
                    <div class="payment-row">
                        <span class="label">Transaction ID</span>
                        <span class="value" style="font-family: monospace;">{{ $bayar->midtrans_txn_id ?? '-' }}</span>
                    </div>
                    <div class="payment-row">
                        <span class="label">Waktu</span>
                        <span class="value">{{ $bayar->created_at->format('d F Y H:i') }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if($transaksi->status === 'paid')
            <div class="stamp">
                <div class="box">LUNAS</div>
            </div>
        @endif
    </div>

    <div class="footer">
        <p class="company">PT 4Putra Vertex Aviary</p>
        <p>Penangkaran Burung Paruh Bengkok Premium — Surabaya Barat, Jawa Timur</p>
        <p style="margin-top: 5px;">Dokumen ini dicetak secara otomatis oleh sistem dan sah sebagai bukti transaksi.</p>
    </div>
</body>
</html>
