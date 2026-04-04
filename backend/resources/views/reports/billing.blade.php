<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tagihan</title>
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
    <h2>Laporan Tagihan Air</h2>
    <p class="info">Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kode Pelanggan</th>
                <th>Nama</th>
                <th>Periode</th>
                <th>Pemakaian</th>
                <th>Biaya Pemakaian</th>
                <th>Abodemen</th>
                <th>Denda</th>
                <th>Total</th>
                <th>Status</th>
                <th>Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bills as $bill)
            <tr>
                <td>{{ $bill->id }}</td>
                <td>{{ $bill->customer->customer_code }}</td>
                <td>{{ $bill->customer->user->name }}</td>
                <td>{{ $bill->billing_period_month }}/{{ $bill->billing_period_year }}</td>
                <td>{{ $bill->usage_m3 }} m³</td>
                <td>Rp {{ number_format($bill->usage_charge, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($bill->abodemen, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($bill->penalty_amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                <td>{{ $bill->status }}</td>
                <td>{{ $bill->due_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
