<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Services\WhatsappBotEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappBotEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Team $team;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@trask.test',
            'password' => 'password',
            'phone' => '+628111222333',
        ]);

        $this->team = Team::create([
            'owner_id' => $this->user->id,
            'name' => 'Kedai Kita',
            'invite_code' => 'KEDAI-TEST',
        ]);

        $this->user->update(['current_team_id' => $this->team->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'member']);
    }

    protected function engine(): WhatsappBotEngine
    {
        return new WhatsappBotEngine($this->team, $this->user->phone);
    }

    public function test_masuk_creates_income_transaction(): void
    {
        $response = $this->engine()->processCommand('masuk 50rb kopi susu #penjualan');

        $this->assertStringContainsString('Pemasukan Berhasil', $response);
        $this->assertStringContainsString('Rp50.000', $response);
        $this->assertStringContainsString('Penjualan', $response);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->team->id,
            'type' => 'income',
            'amount' => 50000,
            'description' => 'kopi susu',
            'category' => 'penjualan',
            'source' => 'whatsapp',
        ]);
    }

    public function test_keluar_creates_expense_transaction(): void
    {
        $response = $this->engine()->processCommand('keluar 25k es batu #bahanbaku');

        $this->assertStringContainsString('Pengeluaran Berhasil', $response);
        $this->assertStringContainsString('Rp25.000', $response);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->team->id,
            'type' => 'expense',
            'amount' => 25000,
        ]);
    }

    public function test_invalid_nominal_asks_for_retry_without_saving(): void
    {
        $response = $this->engine()->processCommand('masuk abc kopi');

        $this->assertStringContainsString('Format nominal tidak dikenali', $response);
        $this->assertStringContainsString('Mohon coba lagi', $response);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_jual_decrements_stock_and_records_sale(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'sku' => 'KS-01',
            'name' => 'Kopi Susu',
            'stock_initial' => 50,
            'stock_current' => 50,
            'stock_minimum' => 5,
            'capital_price' => 4000,
            'selling_price' => 15000,
        ]);

        $response = $this->engine()->processCommand('jual KS-01 x3');

        $this->assertStringContainsString('Penjualan Berhasil', $response);
        $this->assertStringContainsString('Rp45.000', $response);
        $this->assertSame(47, $product->fresh()->stock_current);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->team->id,
            'type' => 'income',
            'amount' => 45000,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_jual_rejects_insufficient_stock(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'sku' => 'KS-02',
            'name' => 'Kopi Hitam',
            'stock_initial' => 2,
            'stock_current' => 2,
            'stock_minimum' => 5,
            'capital_price' => 3000,
            'selling_price' => 10000,
        ]);

        $response = $this->engine()->processCommand('jual KS-02 x5');

        $this->assertStringContainsString('tidak cukup', $response);
        $this->assertSame(2, $product->fresh()->stock_current);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_jual_unknown_product_returns_error(): void
    {
        $response = $this->engine()->processCommand('jual tidak-ada x1');

        $this->assertStringContainsString('tidak ditemukan', $response);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cek_saldo_returns_summary(): void
    {
        $response = $this->engine()->processCommand('cek saldo');

        $this->assertStringContainsString('Ringkasan', $response);
        $this->assertStringContainsString('Saldo Kas', $response);
        $this->assertStringContainsString('Omset Bulan Ini', $response);
    }

    public function test_help_lists_commands(): void
    {
        $response = $this->engine()->processCommand('help');

        $this->assertStringContainsString('masuk <nominal>', $response);
        $this->assertStringContainsString('jual <sku/nama>', $response);
    }
}
