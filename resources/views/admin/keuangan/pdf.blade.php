<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - PT 4Putra Vertex Aviary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1a1a2e; font-size: 12px; line-height: 1.5; }
        .header { background: linear-gradient(135deg, #E62C37 0%, #c5242d 100%); color: white; padding: 30px 40px; }
        .header h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .header .date { text-align: right; font-size: 11px; opacity: 0.8; margin-top: 8px; }
        .summary { display: flex; gap: 15px; padding: 20px 40px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; }
        .summary-card { flex: 1; background: white; border-radius: 8px; padding: 15px; border: 1px solid #e8e8e8; }
        .summary-card .label { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
        .summary-card .value.green { color: #16a34a; }
        .summary-card .value.red { color: #E62C37; }
        .filter { padding: 12px 40px; background: #eef2f7; font-size: 11px; color: #555; }
        .content { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1a1a2e; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .masuk { color: #16a34a; font-weight: 700; }
        .keluar { color: #E62C37; font-weight: 700; }
        .footer { padding: 20px 40px; border-top: 2px solid #E62C37; margin-top: 20px; }
        .footer p { font-size: 10px; color: #888; }
        .footer .company { font-weight: 700; color: #1a1a2e; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>PT 4PUTRA VERTEX AVIARY</h1>
                <p>Penangkaran Burung Paruh Bengkok Premium - Surabaya Barat</p>
            </div>
            <div class="date">
                <div style="font-size: 14px; font-weight: 700;">LAPORAN KEUANGAN</div>
                <div>Dicetak: {{ now()->format('d F Y H:i') }}</div>
            </div>
        </div>
    </div>

    @if ($tab)
        <div class="filter">Proyek: {{ $tab->nama }}</div>
    @endif

    @if ($tipeAktif || $kategoriAktif)
        <div class="filter">
            Filter aktif: {{ $tipeAktif ? 'Tipe '.ucfirst($tipeAktif) : '' }}{{ $tipeAktif && $kategoriAktif ? ' · ' : '' }}{{ $kategoriAktif ? 'Kategori '.$kategoriAktif : 'Semua kategori' }}
        </div>
    @endif

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Masuk</div>
            <div class="value green">Rp {{ number_format((float) $ringkasan['masuk'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Keluar</div>
            <div class="value red">Rp {{ number_format((float) $ringkasan['keluar'], 0, ',', '.') }}</div>
        </div>
        @if ($tab)
            <div class="summary-card">
                <div class="label">Total Budget</div>
                <div class="value">Rp {{ number_format((float) $totalBudget, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Sisa Budget</div>
                <div class="value {{ $sisaBudget < 0 ? 'red' : '' }}">Rp {{ number_format((float) $sisaBudget, 0, ',', '.') }}</div>
            </div>
        @else
            <div class="summary-card">
                <div class="label">Saldo</div>
                <div class="value">Rp {{ number_format((float) $ringkasan['saldo'], 0, ',', '.') }}</div>
            </div>
        @endif
        <div class="summary-card">
            <div class="label">Jumlah Transaksi</div>
            <div class="value">{{ $transaksis->count() }}</div>
        </div>
    </div>

    @if ($tab)
        {{-- Target anggaran & rincian biaya tambahan (khusus tab proyek) --}}
        <div class="content">
            <h2 style="font-size: 14px; margin-bottom: 8px;">Target Anggaran &amp; Rincian Biaya — {{ $tab->nama }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>Target Anggaran Utama</th>
                        <th style="text-align: right;">Rp {{ number_format((float) $tab->target_anggaran, 0, ',', '.') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tab->costs as $c)
                        <tr>
                            <td>{{ $c->nama }} <span style="color:#888;">(biaya tambahan)</span></td>
                            <td style="text-align: right;">Rp {{ number_format((float) $c->nominal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="text-align: center; color: #888;">Tidak ada biaya tambahan</td></tr>
                    @endempty
                    <tr style="background: #eef2f7; font-weight: 700;">
                        <td>TOTAL BUDGET</td>
                        <td style="text-align: right;">Rp {{ number_format((float) $totalBudget, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight: 700;">
                        <td>SISA BUDGET (total budget − pengeluaran)</td>
                        <td style="text-align: right;" class="{{ $sisaBudget < 0 ? 'keluar' : '' }}">Rp {{ number_format((float) $sisaBudget, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksis as $i => $trx)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $trx->tanggal->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($trx->tipe) }}</td>
                        <td>{{ $trx->kategori }}</td>
                        <td class="{{ $trx->tipe === 'masuk' ? 'masuk' : 'keluar' }}">Rp {{ number_format((float) $trx->jumlah, 0, ',', '.') }}</td>
                        <td>{{ $trx->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: #888;">Belum ada transaksi keuangan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p class="company">PT 4Putra Vertex Aviary</p>
        <p>Penangkaran Burung Paruh Bengkok Premium - Surabaya Barat, Jawa Timur</p>
        <p style="margin-top: 5px;">Dokumen ini dicetak secara otomatis oleh sistem. Tanda tangan tidak diperlukan.</p>
    </div>
</body>
</html>
