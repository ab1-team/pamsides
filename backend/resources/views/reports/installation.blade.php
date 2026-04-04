<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Instalasi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
        .info { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Laporan Instalasi Sambungan Air</h2>
    <p class="info">Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Pemohon</th>
                <th>NIK</th>
                <th>Alamat</th>
                <th>Paket</th>
                <th>Status</th>
                <th>Tanggal Pengajuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td>{{ $ticket->applicant_name }}</td>
                <td>{{ $ticket->nik }}</td>
                <td>{{ $ticket->address }}</td>
                <td>{{ $ticket->package->name }}</td>
                <td>{{ $ticket->status }}</td>
                <td>{{ $ticket->created_at->format('d-m-Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
