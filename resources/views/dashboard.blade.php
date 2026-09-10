@extends('components.layout')

@section('title', 'Dashboard - Trask')
@section('header', 'Dashboard Utama')

@section('content')
<div class="space-y-8">
    <!-- Metrics Cards (Saldo Kas, Omset Bulan Ini, Laba Bersih, Nilai Stok) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Saldo Kas</p>
                    <p class="text-2xl font-bold text-slate-800">Rp {{ number_format($metrics->saldoKas(), 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Total Modal: Rp {{ number_format($metrics->totalModalAwal(), 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="wallet-cards" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Real-time DB</span>
                <a href="/transactions" class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                    Detail <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Omset Bulan Ini</p>
                    <p class="text-2xl font-bold text-slate-800">Rp {{ number_format($metrics->omsetBulanIni(), 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Bulan {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span>HPP: Rp {{ number_format($metrics->hpp(), 0, ',', '.') }}</span>
                <span class="text-blue-600 font-medium">Laba Kotor: Rp {{ number_format($metrics->labaKotor(), 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Laba Bersih</p>
                    <p class="text-2xl font-bold text-slate-800">Rp {{ number_format($metrics->labaBersih(), 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ number_format($metrics->marginLabaBersih(), 1) }}%</p>
                </div>
                <div class="p-3 rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="pie-chart" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Beban Tetap: Rp {{ number_format($metrics->totalModalTetap(), 0, ',', '.') }}</span>
                <span class="text-emerald-600 font-medium">Margin Sehat</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Nilai Stok Tersedia</p>
                    <p class="text-2xl font-bold text-slate-800">Rp {{ number_format($metrics->nilaiStokTersedia(), 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $team->products()->count() }} Produk terdaftar</p>
                </div>
                <div class="p-3 rounded-lg bg-purple-50 text-purple-600">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Stok Kritis: {{ $lowStockProducts->count() }} item</span>
                <a href="/products" class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                    Stok <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Grafik Cashflow (Pemasukan vs Pengeluaran) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-slate-800">Tren Cashflow</h3>
                    <p class="text-sm text-slate-500">Pemasukan vs Pengeluaran 7 hari terakhir</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg">Real Data</span>
            </div>
            <canvas id="cashflowChart" height="250"></canvas>
        </div>

        <!-- Komposisi Pengeluaran & Quick Actions -->
        <div class="space-y-6">
            <!-- Komposisi Pengeluaran -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-6">Komposisi Pengeluaran</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <span class="text-sm text-slate-700">Total Pengeluaran Bulan Ini</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-slate-800">Rp {{ number_format($metrics->totalPengeluaranBulanIni(), 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                            <span class="text-sm text-slate-700">Beban Tetap / Bulan</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-slate-800">Rp {{ number_format($metrics->totalModalTetap(), 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="/whatsapp" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 font-medium py-3 px-4 rounded-lg text-sm flex flex-col items-center gap-2 transition text-center">
                        <i data-lucide="message-square" class="w-5 h-5"></i>
                        Input via WhatsApp
                    </a>
                    <a href="/capitals" class="bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 font-medium py-3 px-4 rounded-lg text-sm flex flex-col items-center gap-2 transition text-center">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        Tambah Modal
                    </a>
                    <a href="/products" class="bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 font-medium py-3 px-4 rounded-lg text-sm flex flex-col items-center gap-2 transition text-center">
                        <i data-lucide="package-plus" class="w-5 h-5"></i>
                        Restock Stok
                    </a>
                    <a href="/transactions/export" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium py-3 px-4 rounded-lg text-sm flex flex-col items-center gap-2 transition text-center">
                        <i data-lucide="download" class="w-5 h-5"></i>
                        Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions & Low Stock -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Transaksi Terbaru -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-slate-800">Transaksi Terbaru</h3>
                <a href="/transactions" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                    Lihat Semua <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
            <div class="space-y-4">
                @forelse($recentTransactions as $transaction)
                <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-b-0">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg {{ $transaction->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center">
                            @if($transaction->type === 'income')
                                <i data-lucide="arrow-down-right" class="w-4 h-4"></i>
                            @else
                                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $transaction->description }}</p>
                            <p class="text-xs text-slate-500">{{ $transaction->transacted_at->format('d M, H:i') }} • {{ $transaction->source === 'whatsapp' ? 'WA Bot' : ($transaction->user->name ?? 'Web') }}</p>
                        </div>
                    </div>
                    <p class="font-bold {{ $transaction->type === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $transaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                    </p>
                </div>
                @empty
                <p class="text-sm text-slate-400 py-4 text-center">Belum ada transaksi.</p>
                @endforelse
            </div>
        </div>

        <!-- Stok Hampir Habis & WhatsApp Bot Status -->
        <div class="space-y-6">
            <!-- Stok Hampir Habis -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-slate-800">Stok Hampir Habis</h3>
                    <a href="/products" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                        Restock <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </a>
                </div>
                <div class="space-y-4">
                    @forelse($lowStockProducts as $product)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">Stok: {{ $product->stock_current }} pcs • Minimal: {{ $product->stock_minimum }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($product->stock_current <= ($product->stock_minimum / 2))
                            <span class="text-xs font-medium px-2 py-1 bg-rose-50 text-rose-700 rounded">Kritis</span>
                            @else
                            <span class="text-xs font-medium px-2 py-1 bg-amber-50 text-amber-700 rounded">Peringatan</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-slate-400 py-4 text-center">Semua stok dalam batas aman.</p>
                    @endforelse
                </div>
            </div>

            <!-- WhatsApp Bot Status -->
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg">WhatsApp Bot 2-Way</h3>
                    <div class="px-3 py-1 bg-white/20 rounded-full text-xs font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        {{ $whatsappActive ? 'Aktif' : 'Non-Aktif' }}
                    </div>
                </div>
                <p class="text-sm text-emerald-100 mb-4">Gunakan bot WhatsApp untuk input transaksi langsung dari chat.</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                            <i data-lucide="phone" class="w-3 h-3"></i>
                        </div>
                        <span>+62 812-3456-7890</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                            <i data-lucide="message-square" class="w-3 h-3"></i>
                        </div>
                        <span>{{ $whatsappLogsCount }} interaksi tercatat</span>
                    </div>
                </div>
                <a href="/whatsapp" class="mt-6 block text-center bg-white text-emerald-600 hover:bg-emerald-50 font-medium py-2.5 rounded-lg text-sm transition">
                    Buka Simulator & Log
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('cashflowChart').getContext('2d');
        const labels = {!! json_encode(array_keys($cashflowDays)) !!};
        const incomeData = {!! json_encode(array_column($cashflowDays, 'income')) !!};
        const expenseData = {!! json_encode(array_column($cashflowDays, 'expense')) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.map(l => l.split('-').slice(1).join('/')),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        fill: true,
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Rp ${new Intl.NumberFormat('id-ID').format(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => `Rp ${val / 1000}k`
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
