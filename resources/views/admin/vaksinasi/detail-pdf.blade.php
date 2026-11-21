<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Riwayat Obat &amp; Vaksinasi {{ $kandang->label }} - PT 4Putra Vertex Aviary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; color: #1a1a2e; font-size: 12px; line-height: 1.5; }
        .header { background: linear-gradient(135deg, #E62C37, #c5242d); color: white; padding: 30px 40px; }
        .header h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .header p { font-size: 11px; opacity: 0.9; margin-top: 4px; }
        .header .date { text-align: right; font-size: 10px; opacity: 0.85; }
        .content { padding: 30px 40px; }
        .info { background: #f5f5f7; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1a1a2e; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e5ea; font-size: 11px; }
        tr:nth-child(even) { background: #fafafa; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 14px 40px; font-size: 9px; color: #8a8a8f; border-top: 1px solid #e5e5ea; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <h1>RIWAYAT OBAT &amp; VAKSINASI — {{ strtoupper($kandang->label) }}</h1>
                <p>PT 4Putra Vertex Aviary</p>
            </div>
            <div class="date">Dicetak: {{ now('Asia/Jakarta')->translatedFormat('d M Y H:i') }} WIB</div>
        </div>
    </div>
    <div class="content">
        <div class="info">
            <strong>Kandang:</strong> {{ $kandang->label }} &nbsp;·&nbsp;
            <strong>Jumlah burung:</strong> {{ $burungList->count() }} &nbsp;·&nbsp;
            <strong>Total catatan:</strong> {{ $riwayat->count() }}
        </div>
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
                @forelse ($riwayat as $v)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $v->burung?->nama_ring ?? '-' }}</td>
                        <td>{{ $v->jenis }}</td>
                        <td>{{ $v->metode === 'oral' ? 'Oral' : 'Suntik' }}</td>
                        <td>{{ $v->dosis ?? '—' }}</td>
                        <td>{{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</td>
                        <td>{{ $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:20px;color:#8a8a8f;">Belum ada catatan obat/vaksinasi untuk kandang ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="footer">Dokumen ini dihasilkan otomatis dari sistem monitoring farm PT 4Putra Vertex Aviary.</div>
</body>
</html>
