<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Vaksinasi - PT 4Putra Vertex Aviary</title>
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
        .summary-card .value.red { color: #E62C37; }
        .content { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1a1a2e; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        tbody tr:nth-child(even) { background: #fafafa; }
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
                <div style="font-size: 14px; font-weight: 700;">LAPORAN OBAT & VAKSINASI</div>
                <div>Dicetak: {{ now()->format('d F Y H:i') }}</div>
            </div>
        </div>
    </div>

    @php($jatuhTempo = $riwayat->groupBy('burung_id')->map(fn ($grup) => $grup->first())->filter(fn ($v) => $v->sudah_jatuh_tempo))
    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Catatan</div>
            <div class="value">{{ $riwayat->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Burung Tertangani</div>
            <div class="value">{{ $riwayat->groupBy('burung_id')->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Perlu Ulang (suntik 1 tahun / oral 3 bulan)</div>
            <div class="value {{ $jatuhTempo->isNotEmpty() ? 'red' : '' }}">{{ $jatuhTempo->count() }} burung</div>
        </div>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Burung</th>
                    <th>Jenis</th>
                    <th>Metode</th>
                    <th>Dosis</th>
                    <th>Tanggal</th>
                    <th>Status Ulang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $i => $v)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $v->burung->nama }}{{ $v->burung->nomor_ring ? ' (Ring '.$v->burung->nomor_ring.')' : '' }}</div>
                            <div style="color: #888; font-size: 10px;">{{ $v->burung->spesies }}{{ $v->burung->kandang ? ' · Kandang '.$v->burung->kandang->label : '' }}</div>
                        </td>
                        <td>{{ $v->jenis }}</td>
                        <td>{{ $v->metode === 'oral' ? 'Oral' : 'Suntik' }}</td>
                        <td>{{ $v->dosis ?? '—' }}</td>
                        <td>{{ $v->tanggal_vaksin->format('d/m/Y') }}</td>
                        <td style="font-weight: 600; color: {{ $v->sudah_jatuh_tempo ? '#E62C37' : '#16a34a' }};">
                            {{ $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; padding: 30px; color: #888;">Belum ada catatan</td></tr>
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
