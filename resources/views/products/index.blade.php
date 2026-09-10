@extends('components.layout')

@section('title', 'Produk & Stok - Trask')
@section('header', 'Kelola Produk & Stok')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false, editModalOpen: false, editProduct: {} }">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Daftar Produk & Stok</h2>
            <p class="text-sm text-slate-500">Kelola harga jual, harga modal, dan ambang batas stok minimal</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="modalOpen = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Produk Baru
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter & Summary Bar (live, tanpa reload) -->
    <form data-live-filter data-target="#product-list-wrap" method="GET" action="{{ route('products.index') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative sm:w-64">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk, SKU..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>
            <x-filter-select
                name="category"
                :value="request('category', '')"
                placeholder="Semua Kategori"
                icon="tags"
                :options="[
                    ['value' => 'minuman', 'label' => 'Minuman', 'icon' => 'cup-soda', 'iconClass' => 'bg-sky-100 text-sky-600'],
                    ['value' => 'makanan', 'label' => 'Makanan', 'icon' => 'utensils', 'iconClass' => 'bg-amber-100 text-amber-600'],
                    ['value' => 'bahanbaku', 'label' => 'Bahan Baku', 'icon' => 'wheat', 'iconClass' => 'bg-lime-100 text-lime-700'],
                    ['value' => 'kemasan', 'label' => 'Kemasan', 'icon' => 'package', 'iconClass' => 'bg-violet-100 text-violet-600'],
                ]"
            />
            <button type="submit" class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">Filter</button>
        </div>

        <div class="flex items-center gap-4 text-sm text-slate-600">
            <div class="text-center">
                <p class="font-bold text-slate-800">{{ $products->count() }}</p>
                <p class="text-xs text-slate-400">Total Produk</p>
            </div>
            <div class="text-center">
                <p class="font-bold text-rose-600">{{ $products->where('stock_current', '<=', 'stock_minimum')->count() }}</p>
                <p class="text-xs text-slate-400">Perlu Restock</p>
            </div>
        </div>
    </form>

    <!-- Products Table -->
    <div id="product-list-wrap" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-opacity">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Produk</th>
                        <th class="py-3.5 px-4 text-right">Stok Sekarang</th>
                        <th class="py-3.5 px-4 text-right">Stok Minimal</th>
                        <th class="py-3.5 px-4 text-right">Harga Modal</th>
                        <th class="py-3.5 px-4 text-right">Harga Jual</th>
                        <th class="py-3.5 px-4 text-right">Nilai Stok</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($product->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-400">SKU: {{ $product->sku }} • {{ $product->category ?? 'Umum' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-800">
                            {{ $product->stock_current }} pcs
                        </td>
                        <td class="py-3 px-4 text-right text-slate-500">
                            {{ $product->stock_minimum }} pcs
                        </td>
                        <td class="py-3 px-4 text-right">
                            Rp {{ number_format($product->capital_price, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-slate-800">
                            Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-600">
                            Rp {{ number_format($product->stock_current * $product->capital_price, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($product->stock_current <= ($product->stock_minimum / 2))
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Kritis
                            </span>
                            @elseif($product->stock_current <= $product->stock_minimum)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Peringatan
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aman
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button @click="editProduct = {{ json_encode($product) }}; editModalOpen = true" class="p-1.5 text-indigo-600 hover:text-indigo-700 rounded hover:bg-indigo-50">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-slate-400">Belum ada produk.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah Produk -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100" @click.away="modalOpen = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Tambah Produk Baru</h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('products.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Produk</label>
                        <input type="text" name="name" required placeholder="Kopi Susu" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kode SKU</label>
                        <input type="text" name="sku" required placeholder="KS-01" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stok Awal</label>
                        <input type="number" name="stock_initial" required min="0" placeholder="50" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stok Minimal</label>
                        <input type="number" name="stock_minimum" required min="0" placeholder="10" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Harga Modal (Rp)</label>
                        <input type="number" name="capital_price" required min="0" placeholder="4000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Harga Jual (Rp)</label>
                        <input type="number" name="selling_price" required min="0" placeholder="15000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori</label>
                    <div class="relative">
                        <select name="category" class="appearance-none w-full pl-3.5 pr-8 py-2 bg-slate-50 hover:bg-slate-100/80 border border-slate-200 rounded-lg text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer transition">
                            <option value="minuman">Minuman</option>
                            <option value="makanan">Makanan</option>
                            <option value="bahanbaku">Bahan Baku</option>
                            <option value="kemasan">Kemasan</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex gap-2">
                    <button type="button" @click="modalOpen = false" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition shadow-sm">
                        Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Edit Produk & Update Stok -->
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100" @click.away="editModalOpen = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Update Produk / Restock</h3>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/products/' + editProduct.id" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Produk</label>
                    <input type="text" name="name" x-model="editProduct.name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stok Saat Ini</label>
                        <input type="number" name="stock_current" x-model="editProduct.stock_current" required min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-indigo-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stok Minimal</label>
                        <input type="number" name="stock_minimum" x-model="editProduct.stock_minimum" required min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Harga Modal</label>
                        <input type="number" name="capital_price" x-model="editProduct.capital_price" required min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Harga Jual</label>
                        <input type="number" name="selling_price" x-model="editProduct.selling_price" required min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex gap-2">
                    <button type="button" @click="editModalOpen = false" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition shadow-sm">
                        Update Produk
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
