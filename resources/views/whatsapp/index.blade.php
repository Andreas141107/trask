@extends('components.layout')
@section('title', 'WhatsApp Bot 2-Way - Trask')
@section('header', 'WhatsApp Bot Simulator & Webhook')
@section('content')
<div class="space-y-6" x-data="{
    messages: [],
    inputMessage: '',
    sending: false,
    testingConnection: false,
    connectionResult: null,
    async loadHistory() {
        try {
            const res = await fetch('{{ route('whatsapp.history') }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.messages = data.messages ?? [];
            if (this.messages.length === 0) {
                this.messages = [{ sender: 'bot', text: 'Halo! Saya bot Trask.\nCoba: masuk 50rb kopi #penjualan', time: 'Sekarang' }];
            }
            if (data.phone && document.getElementById('wa-sender')) document.getElementById('wa-sender').value = data.phone;
            if (data.bot_number && document.getElementById('wa-bot-number')) document.getElementById('wa-bot-number').value = data.bot_number;
        } catch (e) {
            this.messages = [{ sender: 'bot', text: 'Gagal memuat riwayat. Coba lagi.', time: 'Sekarang' }];
        }
    },
    async sendMessage() {
        if (!this.inputMessage.trim() || this.sending) return;
        const text = this.inputMessage;
        this.inputMessage = '';
        this.sending = true;
        this.messages.push({ sender: 'user', text: text, time: 'Sekarang' });
        try {
            const res = await fetch('{{ route('whatsapp.send') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            this.messages.push({ sender: 'bot', text: res.ok ? data.reply : (data.message ?? 'Gagal memproses pesan.'), time: 'Sekarang' });
        } catch (e) {
            this.messages.push({ sender: 'bot', text: 'Koneksi gagal. Pastikan server jalan.', time: 'Sekarang' });
        }
        this.sending = false;
    },
    async testConnection() {
        if (this.testingConnection) return;
        this.testingConnection = true;
        this.connectionResult = null;
        try {
            const res = await fetch('{{ route('whatsapp.test-connection') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            });
            const data = await res.json();
            this.connectionResult = {
                ok: res.ok && data.connected,
                message: data.message,
                details: data.device_status ? `Device: ${data.device_status}${data.quota ? ` | Kuota: ${data.quota}` : ''}` : ''
            };
        } catch (e) {
            this.connectionResult = { ok: false, message: 'Koneksi ke server gagal.', details: '' };
        }
        this.testingConnection = false;
    }
}" x-init="loadHistory()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-4">Pengaturan Gateway</h3>
                @if(session('success'))
                    <div class="mb-4 px-3 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('whatsapp.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Provider</label>
                        <x-filter-select
                            name="provider"
                            :value="old('provider', $link?->provider ?? 'fonnte')"
                            placeholder="Semua Provider"
                            icon="plug"
                            :submit="false"
                            :options="[
                                ['value' => 'fonnte', 'label' => 'Fonnte (Rekomendasi MVP)', 'icon' => 'zap', 'iconClass' => 'bg-emerald-100 text-emerald-600'],
                                ['value' => 'wablas', 'label' => 'Wablas', 'icon' => 'send', 'iconClass' => 'bg-indigo-100 text-indigo-600'],
                            ]"
                        />
                        @error('provider')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Webhook Token (kunci URL)</label>
                        <input name="webhook_token" type="text" value="{{ old('webhook_token', $link?->webhook_token ?? '') }}" placeholder="Otomatis dibuat bila kosong" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono">
                        @error('webhook_token')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor HP Kamu (pengirim)</label>
                        <input id="wa-sender" type="text" value="{{ auth()->user()->phone ?? '' }}" disabled class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-500 font-mono">
                        <p class="text-[11px] text-slate-500 mt-1">Ubah di menu Profil bila nomor HP berganti.</p>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg text-sm transition">Simpan Pengaturan</button>
                    <button type="button" @click="testConnection()" :disabled="testingConnection" class="w-full bg-slate-100 hover:bg-slate-200 disabled:opacity-60 text-slate-700 border border-slate-200 font-medium py-2.5 rounded-lg text-sm transition">
                        <span x-text="testingConnection ? 'Mengecek koneksi...' : 'Test Connection'"></span>
                    </button>
                    <div x-show="connectionResult" x-cloak :class="connectionResult?.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'" class="px-3 py-2 rounded-lg border text-sm">
                        <p class="font-medium" x-text="connectionResult?.message"></p>
                        <p x-show="connectionResult?.details" class="text-xs mt-1" x-text="connectionResult?.details"></p>
                    </div>
                </form>
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Webhook URL (pasang di device → edit)</label>
                    <input type="text" value="{{ $webhookUrl }}" readonly onclick="this.select()" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs font-mono text-slate-600">
                </div>
                <div class="pt-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium {{ $link ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }} w-full justify-center">
                        <span class="w-2 h-2 rounded-full {{ $link ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        {{ $link ? 'Konfigurasi tersimpan — siap menerima webhook' : 'Belum terhubung — isi form di atas' }}
                    </span>
                </div>
            </div>
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                <h4 class="font-bold text-indigo-900 text-sm mb-2">Chat dari HP ke Server</h4>
                <ol class="text-xs text-indigo-800 space-y-1.5 list-decimal list-inside">
                    <li>Jalankan <span class="font-mono">php artisan serve</span> lalu expose HTTPS: <span class="font-mono">ngrok http 8000</span></li>
                    <li>Di dashboard Fonnte → device → edit: tempel Webhook URL di atas, set <b>autoread ON</b></li>
                    <li>Chat dari HP ke nomor bot: <span class="font-mono">masuk 50rb kopi #penjualan</span></li>
                    <li>Bot membalas otomatis + transaksi tercatat di dashboard</li>
                </ol>
                <h4 class="font-bold text-indigo-900 text-sm mt-4 mb-2 flex items-center gap-2"><i data-lucide="terminal" class="w-4 h-4"></i> Panduan Perintah Bot WA</h4>
                <div class="text-xs text-indigo-800 space-y-2 font-mono">
                    <p>• <b>masuk 50rb kopi #penjualan</b></p>
                    <p>• <b>keluar 25k galon air</b></p>
                    <p>• <b>jual KS-01 x3</b></p>
                    <p>• <b>cek saldo / cek omset</b></p>
                    <p>• <b>help</b></p>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2 bg-slate-900 rounded-2xl shadow-xl flex flex-col h-[580px] overflow-hidden border border-slate-800">
            <div class="h-16 bg-slate-950 px-6 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs"><i data-lucide="bot" class="w-5 h-5"></i></div>
                    <div>
                        <p class="font-bold text-white text-sm">Trask WhatsApp Bot 2-Way Simulator</p>
                        <p class="text-[11px] text-emerald-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Online & Terhubung</p>
                    </div>
                </div>
                <button @click="messages = []" class="text-xs text-slate-400 hover:text-white">Bersihkan Chat</button>
            </div>
            <div class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-900/60">
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'" class="flex">
                        <div :class="msg.sender === 'user' ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-slate-800 text-slate-100 rounded-bl-none border border-slate-700'" class="max-w-[80%] rounded-2xl px-4 py-3 text-sm shadow-sm whitespace-pre-line">
                            <p x-text="msg.text"></p>
                            <p :class="msg.sender === 'user' ? 'text-indigo-200' : 'text-slate-400'" class="text-[10px] mt-1 text-right" x-text="msg.time"></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="p-4 bg-slate-950 border-t border-slate-800 flex gap-2">
                <input type="text" x-model="inputMessage" @keydown.enter="sendMessage()" placeholder="Ketik pesan simulasi WhatsApp (misal: masuk 50rb kopi)..." class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 placeholder-slate-500">
                <button @click="sendMessage()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium flex items-center gap-2 transition"><i data-lucide="send" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection
