@extends('components.layout')
@section('title', 'Kelola Tim - Trask')
@section('header', 'Kelola Tim & Anggota')
@section('content')
<div class="space-y-6" x-data="{ inviteOpen: false }">
    @if(session('success'))
    <div class="px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-slate-800">Masuk ke Tim</h3>
        <p class="text-sm text-slate-500 mt-1">Masukkan kode undangan dari owner tim.</p>
        <form method="POST" action="{{ route('teams.join') }}" class="mt-4 flex flex-col sm:flex-row gap-2">
            @csrf
            <input name="invite_code" value="{{ old('invite_code') }}" maxlength="8" required class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono uppercase">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Masuk ke Tim</button>
        </form>
        @error('invite_code')<p class="text-xs text-rose-600 mt-2">{{ $message }}</p>@enderror
    </div>
    <!-- Daftar Tim Anda (Switch Team) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-slate-800">Daftar Tim Anda</h3>
        <p class="text-sm text-slate-500 mt-1">Pilih tim yang ingin Anda kelola saat ini.</p>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            @forelse($allTeams ?? [] as $t)
            <div class="p-4 rounded-xl border {{ $team && $team->id === $t->id ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/20' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-100/60' }} flex flex-col justify-between gap-3 transition">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-sm truncate">{{ $t->name }}</span>
                        @if($team && $team->id === $t->id)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white">Aktif</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Role: <span class="font-semibold text-slate-700">{{ ucfirst($t->pivot->role ?? 'member') }}</span></p>
                    <p class="text-[11px] font-mono text-slate-400 mt-0.5">Kode: {{ $t->invite_code }}</p>
                </div>
                @if(! $team || $team->id !== $t->id)
                <form method="POST" action="{{ route('teams.switch', $t) }}">
                    @csrf
                    <button type="submit" class="w-full py-1.5 bg-white hover:bg-indigo-600 hover:text-white border border-slate-200 hover:border-indigo-600 text-slate-700 rounded-lg text-xs font-semibold transition">Beralih ke Tim Ini</button>
                </form>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-400">Belum tergabung dalam tim manapun.</p>
            @endforelse
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tim Saat Ini: {{ $team?->name ?? 'Belum ada tim' }}</h2>
            <p class="text-sm text-slate-500 mt-1">
                Kode undangan tim ini: <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ $team?->invite_code ?? '-' }}</span>
                • {{ $team?->users->count() ?? 0 }} anggota terhubung
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="inviteOpen = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Undang Anggota
            </button>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Anggota</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4">No. WhatsApp</th>
                        <th class="py-3.5 px-4">Transaksi Bulan Ini</th>
                        <th class="py-3.5 px-4">Bergabung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($team?->users ?? [] as $member)
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">{{ $member->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $member->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @php $role = $member->pivot->role ?? 'member'; @endphp
                            @if($role === 'owner')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-900 text-white">Owner</span>
                            @elseif($role === 'admin')
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Admin</span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Member</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs font-mono">{{ $member->phone ?? '-' }}</td>
                        <td class="py-3 px-4 font-bold">
                            {{ $team ? \App\Models\Transaction::where('team_id', $team->id)->where('user_id', $member->id)->whereMonth('transacted_at', now()->month)->count() : 0 }}
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-500">{{ $member->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400">Belum ada anggota.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Undang Anggota -->
    <div x-show="inviteOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl" @click.away="inviteOpen = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Undang Anggota Baru</h3>
                <button @click="inviteOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="mt-4 space-y-4">
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-center">
                    <p class="text-xs text-indigo-600 font-medium">Bagikan kode undangan tim ke anggota baru</p>
                    <p class="font-mono font-bold text-2xl text-indigo-700 tracking-widest mt-1">{{ $team->invite_code ?? '-' }}</p>
                    <button onclick="navigator.clipboard.writeText('{{ $team->invite_code ?? '' }}'); alert('Kode undangan disalin!')" class="mt-2 text-xs font-medium text-indigo-600 hover:underline flex items-center gap-1 mx-auto"><i data-lucide="copy" class="w-3 h-3"></i> Salin Kode</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
