@extends('components.layout')

@section('title', 'Modal & Beban - Trask')
@section('header', 'Modal & Beban Operasional')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false, modalType: 'awal' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Modal Awal & Modal Tetap</h2>
            <p class="text-sm text-slate-500">Dana awal operasional dan beban berkala tim</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="modalOpen = true; modalType = 'awal'" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Modal Awal
            </button>
            <button @click="modalOpen = true; modalType = 'tetap'" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="repeat" class="w-4 h-4"></i> Tambah Beban Tetap
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Modal Awal</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($metrics->totalModalAwal(), 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $capitals->where('type', 'awal')->count() }} kali setoran</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Beban Tetap / Bulan</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($metrics->totalModalTetap(), 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Sewa, gaji, listrik, internet</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Saldo Kas Tersedia</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">Rp {{ number_format($metrics->saldoKas(), 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Modal + Pemasukan - Pengeluaran</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Jenis</th>
                        <th class="py-3.5 px-4">Keterangan</th>
                        <th class="py-3.5 px-4">Dicatat Oleh</th>
                        <th class="py-3.5 px-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($capitals as $capital)
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-800">{{ $capital->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="py-3 px-4">
                            @if($capital->type === 'awal')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">Modal Awal</span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Beban Tetap</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $capital->description }}</td>
                        <td class="py-3 px-4 text-xs">{{ $capital->user->name ?? 'User' }}</td>
                        <td class="py-3 px-4 text-right font-bold {{ $capital->type === 'awal' ? 'text-slate-800' : 'text-rose-600' }}">
                            Rp {{ number_format($capital->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400">Belum ada catatan modal/beban tetap.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah Modal -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl" @click.away="modalOpen = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800" x-text="modalType === 'awal' ? 'Tambah Modal Awal' : 'Tambah Beban Tetap (Bulanan)'"></h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" action="{{ route('capitals.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="type" :value="modalType">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                    <input type="number" name="amount" required min="1" placeholder="1000000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Keterangan</label>
                    <input type="text" name="description" required placeholder="Contoh: Setoran modal awal Budi, Sewa tempat bulan ini" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="pt-4 border-t border-slate-100 flex gap-2">
                    <button type="button" @click="modalOpen = false" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium">Batal</button>
                    <button type="submit" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
