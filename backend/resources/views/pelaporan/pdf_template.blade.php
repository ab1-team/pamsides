<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Laporan - {{ $jenisLaporan ?? 'Laporan' }} - {{ $tahun }}</title>
    <style>
        @page { margin: 20mm 18mm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #0f172a; }
        .cover { text-align: center; padding: 40mm 10mm; border: 4px double #1e3a8a; margin: 8mm 4mm; min-height: 220mm; display: flex; flex-direction: column; justify-content: space-between; }
        .cover .brand { display: flex; align-items: center; justify-content: center; gap: 12px; }
        .cover .brand h2 { margin: 0; font-size: 16px; letter-spacing: 0.5px; }
        .cover .brand p { margin: 2px 0 0; font-size: 11px; color: #1e3a8a; letter-spacing: 2px; font-weight: 600; }
        .divider { height: 2px; background: #1e3a8a; margin: 14px 0; }
        .title { margin: 30mm 0; }
        .title .kategori { font-size: 10px; letter-spacing: 4px; color: #64748b; text-transform: uppercase; margin-bottom: 10px; }
        .title h1 { font-size: 24px; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .title h2 { font-size: 14px; margin: 0 0 14px; color: #1e3a8a; font-weight: 600; }
        .title .periode { font-style: italic; color: #475569; font-size: 12px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; padding: 0 10mm; font-size: 11px; }
        .meta .row { display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding: 4px 0; }
        .meta .row span { color: #64748b; }
        .meta .row strong { color: #0f172a; }
        .footer { text-align: center; margin-top: 16px; font-size: 9px; color: #94a3b8; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 10px; }
        table.data th { background: #1e3a8a; color: #fff; }
        .section-title { font-size: 13px; font-weight: 700; color: #1e3a8a; margin: 14px 0 6px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    @php
        $bulanLabel = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];
        $periode = $tanggal && $bulan
            ? ($tanggal . ' ' . ($bulanLabel[$bulan] ?? $bulan) . ' ' . $tahun)
            : ($bulan ? ($bulanLabel[$bulan] ?? $bulan) . ' ' . $tahun : 'Tahun ' . $tahun);
    @endphp

    <div class="cover">
        <div>
            <div class="brand">
                <div>
                    <h2>BUMDes BANGUN KENCANA</h2>
                    <p>"TIRTO MULO"</p>
                </div>
            </div>
            <div class="divider"></div>
        </div>

        <div class="title">
            <p class="kategori">Laporan Pamsides</p>
            <h1>{{ strtoupper($judulLaporan ?? 'Laporan') }}</h1>
            @if(!empty($subJudul))<h2>{{ $subJudul }}</h2>@endif
            <p class="periode">Periode {{ $periode }}</p>
        </div>

        <div>
            <div class="divider"></div>
            <div class="meta">
                <div class="row"><span>Desa</span><strong>Mulo</strong></div>
                <div class="row"><span>Kecamatan</span><strong>Wonosari</strong></div>
                <div class="row"><span>Kabupaten</span><strong>Gunungkidul</strong></div>
                <div class="row"><span>Tahun Buku</span><strong>{{ $tahun }}</strong></div>
            </div>
            <div class="footer">
                <p>Dokumen ini dicetak secara otomatis oleh sistem Pamsides</p>
                <p>{{ $tanggalCetak }}</p>
            </div>
        </div>
    </div>

    @if(!empty($dataHasil) && count($dataHasil) > 0)
        <div class="section-title">Ringkasan Data</div>
        <table class="data">
            <thead><tr><th>#</th><th>Keterangan</th><th>Nilai</th></tr></thead>
            <tbody>
                @foreach($dataHasil as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row['keterangan'] ?? '-' }}</td>
                        <td>{{ $row['nilai'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
