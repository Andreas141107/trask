<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use App\Models\User;

class WhatsappBotEngine
{
    public function __construct(protected Team $team, protected ?string $senderNumber = null) {}

    public function processCommand(string $message): string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $message));

        if ($normalized === '') {
            return $this->handleHelp();
        }

        $lower = strtolower($normalized);

        if ($lower === 'masuk' || str_starts_with($lower, 'masuk ')) {
            return $this->handleCashFlow($normalized, 'income');
        }

        if ($lower === 'keluar' || str_starts_with($lower, 'keluar ')) {
            return $this->handleCashFlow($normalized, 'expense');
        }

        if ($lower === 'jual' || str_starts_with($lower, 'jual ')) {
            return $this->handleSale($normalized);
        }

        if ($lower === 'help' || str_starts_with($lower, 'cek ') || str_contains($lower, 'saldo') || str_contains($lower, 'omset')) {
            if ($lower === 'help') {
                return $this->handleHelp();
            }

            return $this->handleSummary();
        }

        return $this->handleHelp();
    }

    protected function handleCashFlow(string $message, string $type): string
    {
        $parts = explode(' ', $message, 3);

        if (count($parts) < 3) {
            return $this->invalidNominalMessage($type);
        }

        $amount = NominalParser::parse($parts[1]);

        if ($amount === null || $amount <= 0) {
            return $this->invalidNominalMessage($type);
        }

        [$description, $category] = $this->extractDescriptionAndCategory($parts[2], $type === 'income' ? 'penjualan' : 'operasional');

        $transaction = $this->team->transactions()->create([
            'user_id' => $this->resolveUserId(),
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'category' => $category,
            'source' => 'whatsapp',
            'whatsapp_sender' => $this->senderNumber,
            'transacted_at' => now(),
        ]);

        if ($transaction->wasRecentlyCreated === false) {
            return $this->invalidNominalMessage($type);
        }

        $metrics = new FinancialMetricService($this->team->fresh());
        $lines = $type === 'income'
            ? ['✅ Pemasukan Berhasil!', 'Nominal: '.$this->formatRupiah($amount), 'Item: '.$description, 'Kategori: '.ucfirst($category)]
            : ['✅ Pengeluaran Berhasil!', 'Nominal: '.$this->formatRupiah($amount), 'Deskripsi: '.$description, 'Kategori: '.ucfirst($category)];

        $stockLine = $this->matchProductStockLine($description);
        if ($stockLine !== null) {
            $lines[] = $stockLine;
        }

        $lines[] = 'Saldo '.$this->team->name.': '.$this->formatRupiah($metrics->saldoKas());

        return implode("\n", $lines);
    }

    protected function handleSale(string $message): string
    {
        if (! preg_match('/^jual\s+(.+?)(?:\s+x(\d+))?\s*$/i', $message, $matches)) {
            return $this->saleUsageMessage();
        }

        $keyword = trim($matches[1]);
        $quantity = isset($matches[2]) ? (int) $matches[2] : 1;

        if ($keyword === '' || $quantity < 1) {
            return $this->saleUsageMessage();
        }

        $product = $this->team->products()
            ->where(function ($query) use ($keyword) {
                $query->where('sku', $keyword)->orWhere('name', 'like', "%{$keyword}%");
            })
            ->first();

        if (! $product instanceof Product) {
            return "❌ Produk '{$keyword}' tidak ditemukan.\nGunakan SKU atau nama produk.\nContoh: jual KS-01 x3";
        }

        if ($product->stock_current < $quantity) {
            return "❌ Stok {$product->name} tidak cukup.\nSisa: {$product->stock_current} pcs, diminta: {$quantity} pcs.\nRestock dulu sebelum menjual.";
        }

        $amount = (float) $product->selling_price * $quantity;
        $product->decrement('stock_current', $quantity);

        $transaction = $this->team->transactions()->create([
            'user_id' => $this->resolveUserId(),
            'type' => 'income',
            'amount' => $amount,
            'description' => $product->name.' x'.$quantity,
            'category' => $product->category ?? 'penjualan',
            'source' => 'whatsapp',
            'whatsapp_sender' => $this->senderNumber,
            'transacted_at' => now(),
        ]);

        $transaction->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->selling_price,
            'subtotal' => $amount,
        ]);

        $metrics = new FinancialMetricService($this->team->fresh());

        return implode("\n", [
            '✅ Penjualan Berhasil!',
            'Produk: '.$product->name.' x'.$quantity,
            'Nominal: '.$this->formatRupiah($amount),
            'Sisa Stok: '.$product->fresh()->stock_current.' pcs',
            'Saldo '.$this->team->name.': '.$this->formatRupiah($metrics->saldoKas()),
        ]);
    }

    protected function handleSummary(): string
    {
        $metrics = new FinancialMetricService($this->team);

        return implode("\n", [
            '📊 Ringkasan '.$this->team->name.':',
            'Saldo Kas: '.$this->formatRupiah($metrics->saldoKas()),
            'Omset Bulan Ini: '.$this->formatRupiah($metrics->omsetBulanIni()),
            'Laba Bersih: '.$this->formatRupiah($metrics->labaBersih()),
            'Nilai Stok: '.$this->formatRupiah($metrics->nilaiStokTersedia()),
        ]);
    }

    protected function handleHelp(): string
    {
        return implode("\n", [
            '🤖 Perintah Trask Bot:',
            'masuk <nominal> <deskripsi> [#kategori]',
            'keluar <nominal> <deskripsi> [#kategori]',
            'jual <sku/nama> x<qty>',
            'cek saldo / cek omset',
            'Contoh: masuk 50rb kopi #penjualan',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function extractDescriptionAndCategory(string $rest, string $defaultCategory): array
    {
        if (preg_match('/#(\S+)/', $rest, $matches)) {
            $category = strtolower($matches[1]);
            $description = trim(str_replace($matches[0], '', $rest));
        } else {
            $category = $defaultCategory;
            $description = trim($rest);
        }

        if ($description === '') {
            $description = $defaultCategory;
        }

        return [$description, $category];
    }

    protected function matchProductStockLine(string $description): ?string
    {
        $product = $this->team->products()
            ->where('name', 'like', "%{$description}%")
            ->orWhere(function ($query) use ($description) {
                foreach (explode(' ', $description) as $word) {
                    if (strlen($word) >= 4) {
                        $query->orWhere('name', 'like', "%{$word}%");
                    }
                }
            })
            ->first();

        if (! $product instanceof Product) {
            return null;
        }

        return 'Stok '.$product->name.': '.$product->stock_current.' pcs';
    }

    protected function resolveUserId(): ?int
    {
        if ($this->senderNumber === null || trim($this->senderNumber) === '') {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $this->senderNumber);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $candidates = array_unique(array_filter([$this->senderNumber, '+'.$digits]));
        if (str_starts_with($digits, '62')) {
            $candidates[] = '0'.substr($digits, 2);
        }

        $user = $this->team->users()->whereIn('phone', $candidates)->first()
            ?? User::whereIn('phone', $candidates)->first();

        return $user?->id;
    }

    protected function invalidNominalMessage(string $type): string
    {
        $example = $type === 'income' ? 'masuk 50rb kopi #penjualan' : 'keluar 25rb es batu #bahanbaku';

        return implode("\n", [
            'Format nominal tidak dikenali.',
            'Gunakan: 50rb, 1jt, 1.500.000',
            'Contoh: '.$example,
            'Mohon coba lagi.',
        ]);
    }

    protected function saleUsageMessage(): string
    {
        return implode("\n", [
            'Format jual tidak dikenali.',
            'Gunakan: jual <sku/nama> x<qty>',
            'Contoh: jual KS-01 x3',
            'Mohon coba lagi.',
        ]);
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
