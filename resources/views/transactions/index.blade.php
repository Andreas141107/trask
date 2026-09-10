@extends('components.layout')

@section('title', 'Transaksi - Trask')
@section('header', 'Kelola Transaksi')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false, modalType: 'income' }">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Catatan Pemasukan & Pengeluaran</h2>
            <p class="text-sm text-slate-500">Semua transaksi tim tercatat otomatis via web & bot WhatsApp</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="modalOpen = true; modalType = 'income'" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Catat Pemasukan
            </button>
            <button @click="modalOpen = true; modalType = 'expense'" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="minus" class="w-4 h-4"></i>
                Catat Pengeluaran
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter Bar (live, tanpa reload) -->
    <form id="transaction-filter-form" data-live-filter data-target="#transaction-list-wrap" method="GET" action="{{ route('transactions.index') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Search -->
            <div class="relative flex-1 sm:w-64">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi, kategori..." autocomplete="off" class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>
            <!-- Type Filter -->
            <x-filter-select
                name="type"
                :value="request('type', '')"
                placeholder="Semua Tipe"
                icon="arrow-left-right"
                :options="[
                    ['value' => 'income', 'label' => 'Pemasukan (Masuk)', 'icon' => 'trending-up', 'iconClass' => 'bg-emerald-100 text-emerald-600'],
                    ['value' => 'expense', 'label' => 'Pengeluaran (Keluar)', 'icon' => 'trending-down', 'iconClass' => 'bg-rose-100 text-rose-600'],
                ]"
            />

            <!-- Source Filter -->
            <x-filter-select
                name="source"
                :value="request('source', '')"
                placeholder="Semua Sumber"
                icon="radio"
                :options="[
                    ['value' => 'web', 'label' => 'Web Dashboard', 'icon' => 'monitor', 'iconClass' => 'bg-indigo-100 text-indigo-600'],
                    ['value' => 'whatsapp', 'label' => 'WhatsApp Bot', 'icon' => 'message-square', 'iconClass' => 'bg-emerald-100 text-emerald-600'],
                ]"
            />
            <button type="submit" class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">Filter</button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <a href="{{ route('transactions.export', array_merge(request()->query(), ['format' => 'excel'])) }}" data-export-base="{{ route('transactions.export') }}" data-export="excel" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="table" class="w-4 h-4"></i>
                Export Excel
            </a>
            <a href="{{ route('transactions.export', request()->query()) }}" data-export-base="{{ route('transactions.export') }}" data-export="csv" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-500"></i>
                CSV
            </a>
        </div>
    </form>

    <!-- Transactions Table -->
    <div id="transaction-list-wrap" data-live-paginate class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-opacity">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Tanggal & Waktu</th>
                        <th class="py-3.5 px-4">Tipe</th>
                        <th class="py-3.5 px-4">Deskripsi</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Sumber / Pencatat</th>
                        <th class="py-3.5 px-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <p class="font-medium text-slate-800">{{ $tx->transacted_at->format('d M Y') }}</p>
                            <p class="text-xs text-slate-400">{{ $tx->transacted_at->format('H:i') }} WIB</p>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($tx->type === 'income')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Pemasukan
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Pengeluaran
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-800">{{ $tx->description }}</p>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($tx->category)
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs">#{{ $tx->category }}</span>
                            @else
                            <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="p-1 {{ $tx->source === 'whatsapp' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }} rounded text-[10px] font-bold uppercase">{{ $tx->source }}</span>
                                <div>
                                    <p class="text-xs font-medium text-slate-800">{{ $tx->source === 'whatsapp' ? ($tx->whatsapp_sender ?? 'Bot') : ($tx->user->name ?? 'User') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-right font-bold {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400">Tidak ada data transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Form Transaksi -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100" @click.away="modalOpen = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800" x-text="modalType === 'income' ? 'Catat Pemasukan Baru' : 'Catat Pengeluaran Baru'"></h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('transactions.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="type" :value="modalType">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-sm">Rp</span>
                        <input type="number" name="amount" required min="1" placeholder="50000" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Transaksi</label>
                    <input type="text" name="description" required placeholder="Contoh: Penjualan Kopi Susu x2, Beli Es Batu" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori Tag (Opsional)</label>
                    <input type="text" name="category" placeholder="penjualan, operasional, bahanbaku" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <div class="pt-4 border-t border-slate-100 flex gap-2">
                    <button type="button" @click="modalOpen = false" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition">
                        Batal
                    </button>
                    <button type="submit" :class="modalType === 'income' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'" class="w-1/2 py-2.5 text-white rounded-lg text-sm font-medium transition shadow-sm">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    @include('components.live-filter-script')
@endpush
@endsection
