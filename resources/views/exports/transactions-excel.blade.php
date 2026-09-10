<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        .title { font-size: 16pt; font-weight: bold; color: #0f172a; text-align: left; height: 32px; }
        .meta-label { font-weight: bold; color: #475569; width: 160px; }
        .meta-val { color: #0f172a; }
        .section-header { font-size: 12pt; font-weight: bold; background-color: #f1f5f9; color: #0f172a; border-top: 1.5pt solid #cbd5e1; border-bottom: 1.5pt solid #cbd5e1; height: 26px; }
        .summary-card { border: 1pt solid #cbd5e1; background-color: #f8fafc; padding: 6px; }
        .summary-val { font-size: 12pt; font-weight: bold; text-align: right; }
        table.data-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table.data-table th { background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center; border: 1pt solid #334155; padding: 8px; font-size: 10.5pt; }
        table.data-table td { border: 0.5pt solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-income { color: #047857; font-weight: bold; }
        .text-expense { color: #b91c1c; font-weight: bold; }
        .total-row td { font-weight: bold; background-color: #f8fafc; border-top: 1.5pt solid #0f172a; border-bottom: 2pt double #0f172a; height: 26px; }
        .badge-income { background-color: #d1fae5; color: #065f46; font-size: 9pt; font-weight: bold; text-align: center; }
        .badge-expense { background-color: #fee2e2; color: #991b1b; font-size: 9pt; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="12" class="title">LAPORAN ARUS KEUANGAN &amp; TRANSAKSI TRASK</td>
        </tr>
        <tr>
            <td colspan="12" style="color: #64748b; font-size: 10pt;">Ringkasan pencatatan transaksi multi-user berbasis tim real-time</td>
        </tr>
        <tr><td colspan="12"></td></tr>
        <tr>
            <td class="meta-label">Nama Tim / Bisnis</td>
            <td colspan="4" class="meta-val"><b>{{ $team->name ?? '-' }}</b></td>
            <td></td>
            <td class="meta-label">Waktu Export</td>
            <td colspan="5" class="meta-val">{{ now()->translatedFormat('d F Y, H:i:s') }} WIB</td>
        </tr>
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td colspan="4" class="meta-val">{{ Auth::user()->name }} ({{ Auth::user()->email }})</td>
            <td></td>
            <td class="meta-label">Filter Aktif</td>
            <td colspan="5" class="meta-val">Tipe: {{ $filters['type'] }} | Sumber: {{ $filters['source'] }} | Cari: {{ $filters['search'] }}</td>
        </tr>
        <tr><td colspan="12"></td></tr>
        <tr class="section-header">
            <td colspan="12"><b>&nbsp;RINGKASAN EKSEKUTIF PERIODE INI</b></td>
        </tr>
        <tr>
            <td colspan="3" class="summary-card" style="text-align: center;">Total Transaksi<br><span style="font-size: 14pt; font-weight: bold; color: #0f172a;">{{ $transactions->count() }} baris</span></td>
            <td colspan="3" class="summary-card" style="text-align: center;">Total Pemasukan<br><span style="font-size: 14pt; font-weight: bold; color: #047857;">Rp {{ number_format($totalIncome, 2, ',', '.') }}</span></td>
            <td colspan="3" class="summary-card" style="text-align: center;">Total Pengeluaran<br><span style="font-size: 14pt; font-weight: bold; color: #b91c1c;">Rp {{ number_format($totalExpense, 2, ',', '.') }}</span></td>
            <td colspan="3" class="summary-card" style="text-align: center;">Arus Kas Bersih<br><span style="font-size: 14pt; font-weight: bold; color: {{ $netFlow >= 0 ? '#047857' : '#b91c1c' }};">Rp {{ number_format($netFlow, 2, ',', '.') }}</span></td>
        </tr>
        <tr><td colspan="12"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 100px;">ID Transaksi</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 70px;">Jam</th>
                <th style="width: 100px;">Tipe</th>
                <th style="width: 240px;">Deskripsi</th>
                <th style="width: 110px;">Kategori</th>
                <th style="width: 110px;">Sumber</th>
                <th style="width: 150px;">Pencatat / Pengirim</th>
                <th style="width: 130px;">Pemasukan (IDR)</th>
                <th style="width: 130px;">Pengeluaran (IDR)</th>
                <th style="width: 260px;">Detail Item Produk</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center" style="font-family: monospace;">#TX-{{ str_pad((string) $tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td class="text-center">{{ $tx->transacted_at->format('Y-m-d') }}</td>
                <td class="text-center">{{ $tx->transacted_at->format('H:i') }}</td>
                <td class="{{ $tx->type === 'income' ? 'badge-income' : 'badge-expense' }}">
                    {{ $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                </td>
                <td>{{ $tx->description }}</td>
                <td class="text-center">{{ $tx->category ? '#' . $tx->category : '-' }}</td>
                <td class="text-center">{{ $tx->source === 'whatsapp' ? 'WhatsApp Bot' : 'Web Dashboard' }}</td>
                <td>{{ $tx->source === 'whatsapp' ? ($tx->whatsapp_sender ?? 'Bot WA') : ($tx->user?->name ?? 'User') }}</td>
                <td class="text-right text-income">{{ $tx->type === 'income' ? number_format((float) $tx->amount, 2, ',', '.') : '0,00' }}</td>
                <td class="text-right text-expense">{{ $tx->type === 'expense' ? number_format((float) $tx->amount, 2, ',', '.') : '0,00' }}</td>
                <td style="font-size: 10pt; color: #475569;">
                    @php
                        $details = $tx->items->map(function ($item) {
                            $pname = $item->product?->name ?? 'Item';
                            return "{$pname} (x{$item->quantity} @".number_format((float) $item->unit_price, 0, ',', '.').")";
                        })->implode('; ');
                    @endphp
                    {{ $details !== '' ? $details : '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada transaksi pada filter ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right"><b>TOTAL KESELURUHAN (IDR)</b></td>
                <td class="text-right text-income">Rp {{ number_format($totalIncome, 2, ',', '.') }}</td>
                <td class="text-right text-expense">Rp {{ number_format($totalExpense, 2, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td colspan="9" class="text-right"><b>ARUS KAS BERSIH (NET FLOW)</b></td>
                <td colspan="2" class="text-right" style="font-size: 12pt; color: {{ $netFlow >= 0 ? '#047857' : '#b91c1c' }}; font-weight: bold;">
                    Rp {{ number_format($netFlow, 2, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
