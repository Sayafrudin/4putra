<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }} - PT 4Putra Vertex Aviary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1a1a2e; }
        .header { background: linear-gradient(135deg, #E62C37 0%, #c5242d 100%); color: white; padding: 28px 40px; }
        .header h1 { font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { font-size: 11px; opacity: 0.9; margin-top: 4px; }
        .header .date { font-size: 10px; opacity: 0.85; margin-top: 6px; }
        .summary { display: flex; gap: 12px; padding: 16px 40px; background: #f6f7f9; border-bottom: 1px solid #e5e7eb; }
        .summary-card { flex: 1; background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; }
        .summary-card .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 700; }
        .summary-card .value { font-size: 16px; font-weight: 800; margin-top: 2px; }
        .content { padding: 20px 40px; }
        h2 { font-size: 13px; font-weight: 800; margin: 18px 0 8px; color: #111827; }
        h2:first-child { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; padding: 7px 9px; text-align: left; border-bottom: 1.5px solid #d1d5db; }
        td { padding: 6px 9px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
        .kosong { color: #9ca3af; font-style: italic; }
        .penuh { color: #E62C37; font-weight: 700; }
        .footer { margin-top: 24px; padding: 14px 40px; border-top: 2px solid #E62C37; font-size: 9px; color: #6b7280; }
        .footer .company { font-weight: 700; color: #374151; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $judul }}</h1>
        <p>PT 4Putra Vertex Aviary — Penangkaran Burung Paruh Bengkok Premium</p>
        <div class="date">Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="label">{{ $aviary ? 'Total Aviary' : 'Total Kandang' }}</div>
            <div class="value">{{ $kandangs->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Burung Ditempatkan</div>
            <div class="value">{{ $kandangs->sum(fn ($k) => $k->burungs->count()) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Belum Ditempatkan</div>
            <div class="value">{{ $pool->count() }}</div>
        </div>
    </div>

    <div class="content">
        @if ($aviary)
            @forelse($kandangs as $k)
                <h2>{{ $k->label }}</h2>
                @if ($k->burungs->isEmpty())
                    <p class="kosong">Kosong — belum ada burung.</p>
                @else
                    <table>
                        <thead>
                            <tr><th style="width: 30px;">No</th><th>Nama Burung</th><th>Spesies</th><th>Fase</th><th>Gender</th><th>No. Ring</th><th>No. DNA</th><th>Catatan</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($k->burungs as $i => $b)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td style="font-weight: 600;">{{ $b->nama }}</td>
                                    <td>{{ $b->spesies }}</td>
                                    <td>{{ $b->fase_label }}</td>
                                    <td>{{ $b->gender_label ?: '-' }}</td>
                                    <td>{{ $b->nomor_ring ?: '-' }}</td>
                                    <td>{{ $b->nomor_dna ?: '-' }}</td>
                                    <td>{{ $b->catatan ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @empty
                <p class="kosong">Belum ada aviary.</p>
            @endforelse
        @else
            @foreach ($kandangs as $blok => $kandangBlok)
                <h2>Blok {{ $blok }}</h2>
                <table>
                    <thead>
                        <tr><th style="width: 70px;">Kandang</th><th>Tipe</th><th>Isi</th><th>Status</th><th>Isi Burung</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($kandangBlok as $k)
                            @php($isi = $k->burungs->count())
                            <tr>
                                <td style="font-weight: 600;">{{ $k->label }}</td>
                                <td>{{ ucfirst($k->tipe) }}</td>
                                <td>{{ $isi }}/2</td>
                                <td class="{{ $isi === 0 ? 'kosong' : ($isi >= 2 ? 'penuh' : '') }}">{{ $isi === 0 ? 'Kosong' : ($isi >= 2 ? 'Penuh' : $isi.' burung') }}</td>
                                <td>{{ $k->burungs->map(fn ($b) => $b->nama)->implode(', ') ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endif

        <h2>Belum Ditempatkan ({{ $pool->count() }} burung)</h2>
        @if ($pool->isEmpty())
            <p class="kosong">Semua burung sudah ditempatkan.</p>
        @else
            <table>
                <thead>
                    <tr><th style="width: 30px;">No</th><th>Nama Burung</th><th>Spesies</th><th>Fase</th><th>Gender</th><th>No. Ring</th><th>No. DNA</th><th>Catatan</th></tr>
                </thead>
                <tbody>
                    @foreach ($pool as $i => $b)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td style="font-weight: 600;">{{ $b->nama }}</td>
                            <td>{{ $b->spesies }}</td>
                            <td>{{ $b->fase_label }}</td>
                            <td>{{ $b->gender_label ?: '-' }}</td>
                            <td>{{ $b->nomor_ring ?: '-' }}</td>
                            <td>{{ $b->nomor_dna ?: '-' }}</td>
                            <td>{{ $b->catatan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        <p class="company">PT 4Putra Vertex Aviary</p>
        <p>Penangkaran Burung Paruh Bengkok Premium - Surabaya Barat, Jawa Timur</p>
        <p style="margin-top: 4px;">Dokumen ini dicetak secara otomatis oleh sistem. Tanda tangan tidak diperlukan.</p>
    </div>
</body>
</html>
