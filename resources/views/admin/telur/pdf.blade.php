<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Telur & Incubator - PT 4Putra Vertex Aviary</title>
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
        .summary-card .value.purple { color: #7c3aed; }
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
                <div style="font-size: 14px; font-weight: 700;">LAPORAN TELUR & INCUBATOR</div>
                <div>Dicetak: {{ now()->format('d F Y H:i') }}</div>
            </div>
        </div>
    </div>

    @php
        $diinkubasi = $telurs->where('status', 'diinkubasi');
        $menetas = $telurs->where('status', 'menetas');
        $siapDilepas = $menetas->filter(fn ($t) => $t->siap_dilepas);
    @endphp
    <div class="summary">
        <div class="summary-card">
            <div class="label">Telur Diinkubasi</div>
            <div class="value">{{ $diinkubasi->sum('jumlah') }} butir ({{ $diinkubasi->count() }} catatan)</div>
        </div>
        <div class="summary-card">
            <div class="label">Menetas (Incubator)</div>
            <div class="value purple">{{ $menetas->count() }} catatan</div>
        </div>
        <div class="summary-card">
            <div class="label">Siap Dilepas (±90 hari)</div>
            <div class="value green">{{ $siapDilepas->count() }}</div>
        </div>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Induk (Pasangan)</th>
                    <th>Sesi</th>
                    <th>Butir</th>
                    <th>Diambil</th>
                    <th>Usia</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($telurs as $i => $t)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight: 600;">{{ $t->induk_label }}</td>
                        <td>{{ $t->sesi }}</td>
                        <td>{{ $t->jumlah }}</td>
                        <td>{{ $t->tanggal_diambil->format('d/m/Y') }} <span style="color:#888; font-size:10px;">oleh {{ $t->diambil_oleh }}</span></td>
                        <td>
                            @if ($t->status === 'menetas' && $t->tanggal_menetas)
                                {{ $t->usia_menetas_hari }} hari{{ $t->siap_dilepas ? ' (siap dilepas)' : '' }}
                            @else
                                {{ $t->usia_hari }} hari
                            @endif
                        </td>
                        <td>{{ $t->kondisi }}</td>
                        <td style="font-weight: 600;">{{ ucfirst($t->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; padding: 30px; color: #888;">Belum ada catatan telur</td></tr>
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
