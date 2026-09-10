<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use App\Models\Transaction;

class FinancialMetricService
{
    protected Team $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    // Saldo Kas = Total Modal Awal + Total Pemasukan - Total Pengeluaran
    public function saldoKas(): float
    {
        $modalAwal = $this->totalModalAwal();
        $totalPemasukan = $this->totalPemasukan();
        $totalPengeluaran = $this->totalPengeluaran();

        return $modalAwal + $totalPemasukan - $totalPengeluaran;
    }

    // Omset bulan ini (total pemasukan bulan ini)
    public function omsetBulanIni(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->team->transactions()
            ->where('type', 'income')
            ->whereBetween('transacted_at', [$startOfMonth, $endOfMonth])
            ->sum('amount') ?? 0.0;
    }

    // Total modal awal (type 'awal')
    public function totalModalAwal(): float
    {
        return $this->team->capitals()
            ->where('type', 'awal')
            ->sum('amount') ?? 0.0;
    }

    // Total modal tetap (type 'tetap')
    public function totalModalTetap(): float
    {
        return $this->team->capitals()
            ->where('type', 'tetap')
            ->sum('amount') ?? 0.0;
    }

    // Total pemasukan (all-time)
    public function totalPemasukan(): float
    {
        return $this->team->transactions()
            ->where('type', 'income')
            ->sum('amount') ?? 0.0;
    }

    // Total pengeluaran (all-time)
    public function totalPengeluaran(): float
    {
        return $this->team->transactions()
            ->where('type', 'expense')
            ->sum('amount') ?? 0.0;
    }

    // HPP (Harga Pokok Penjualan) = quantity terjual * harga modal produk
    public function hpp(): float
    {
        $hpp = 0.0;

        // Get all transaction items for income transactions
        $transactionIds = $this->team->transactions()
            ->where('type', 'income')
            ->pluck('id');

        if ($transactionIds->isNotEmpty()) {
            // Laravel query builder approach to sum (quantity * product.capital_price)
            // Simpler: direct DB sum with join; for now we calculate per item manually
            $items = $this->team->transactions()
                ->whereIn('id', $transactionIds)
                ->with('items.product')
                ->get()
                ->flatMap->items;

            foreach ($items as $item) {
                if ($item->product && $item->product->capital_price) {
                    $hpp += $item->quantity * $item->product->capital_price;
                }
            }
        }

        return $hpp;
    }

    // Laba Kotor = Omset Bulan Ini - HPP
    public function labaKotor(): float
    {
        return $this->omsetBulanIni() - $this->hpp();
    }

    // Laba Bersih = Laba Kotor - (Pengeluaran + Modal Tetap)
    public function labaBersih(): float
    {
        $pengeluaranBulanIni = $this->totalPengeluaranBulanIni();

        return $this->labaKotor() - ($pengeluaranBulanIni + $this->totalModalTetap());
    }

    public function marginLabaBersih(): float
    {
        $omset = $this->omsetBulanIni();
        if ($omset == 0) {
            return 0.0;
        }

        return ($this->labaBersih() / $omset) * 100;
    }

    // Total pengeluaran bulan ini (excluding modal tetap)
    public function totalPengeluaranBulanIni(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->team->transactions()
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [$startOfMonth, $endOfMonth])
            ->sum('amount') ?? 0.0;
    }

    // Nilai stok tersedia (stock_current * capital_price)
    public function nilaiStokTersedia(): float
    {
        return $this->team->products()
            ->sum(\DB::raw('stock_current * capital_price')) ?? 0.0;
    }

    // Produk terlaris bulan ini
    public function produkTerlaris(): ?Product
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $topProduct = $this->team->products()
            ->withCount(['transactionItems' => function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereHas('transaction', function ($q2) use ($startOfMonth, $endOfMonth) {
                    $q2->where('type', 'income')
                        ->whereBetween('transacted_at', [$startOfMonth, $endOfMonth]);
                });
            }])
            ->orderBy('transaction_items_count', 'desc')
            ->first();

        return $topProduct;
    }
}
