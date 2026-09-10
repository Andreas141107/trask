<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Owner Bisnis',
            'email' => 'owner@bisnis.test',
            'password' => 'password',
        ]);

        $this->team = Team::create([
            'owner_id' => $this->user->id,
            'name' => 'Kedai Kopi Trask',
            'invite_code' => 'KEDAI-EXPORT',
        ]);

        $this->user->update(['current_team_id' => $this->team->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'owner']);
    }

    public function test_export_csv_contains_metadata_and_summary_blocks(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'sku' => 'KP-01',
            'name' => 'Kopi Tubruk',
            'stock_initial' => 20,
            'stock_current' => 15,
            'stock_minimum' => 2,
            'capital_price' => 3000,
            'selling_price' => 10000,
        ]);

        $income = Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 50000,
            'description' => 'Penjualan Kopi Tubruk x5',
            'category' => 'penjualan',
            'source' => 'web',
            'transacted_at' => now(),
        ]);

        $income->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10000,
            'subtotal' => 50000,
        ]);

        Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 15000,
            'description' => 'Beli Es Batu',
            'category' => 'operasional',
            'source' => 'whatsapp',
            'whatsapp_sender' => '+6283872147483',
            'transacted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/transactions/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('LAPORAN ARUS KEUANGAN', $content);
        $this->assertStringContainsString('Kedai Kopi Trask', $content);
        $this->assertStringContainsString('RINGKASAN', $content);
        $this->assertStringContainsString('Total Pemasukan', $content);
        $this->assertStringContainsString('Arus Kas Bersih', $content);
        $this->assertStringContainsString('Penjualan Kopi Tubruk x5', $content);
        $this->assertStringContainsString('Kopi Tubruk (x5 @10.000)', $content);
        $this->assertStringContainsString('WhatsApp Bot', $content);
        $this->assertStringContainsString('+6283872147483', $content);
        $this->assertStringContainsString('TOTAL', $content);
    }

    public function test_export_excel_returns_styled_html_workbook(): void
    {
        Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 75000,
            'description' => 'Jual Paket Bundling',
            'category' => 'penjualan',
            'source' => 'web',
            'transacted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/transactions/export?format=excel');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('LAPORAN ARUS KEUANGAN', $content);
        $this->assertStringContainsString('Kedai Kopi Trask', $content);
        $this->assertStringContainsString('RINGKASAN EKSEKUTIF', $content);
        $this->assertStringContainsString('border-collapse', $content);
        $this->assertStringContainsString('Jual Paket Bundling', $content);
        $this->assertStringContainsString('75.000,00', $content);
        $this->assertStringContainsString('ARUS KAS BERSIH', $content);
    }

    public function test_export_csv_respects_active_filters(): void
    {
        Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 100000,
            'description' => 'Target Income',
            'source' => 'web',
        ]);

        Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 20000,
            'description' => 'Target Expense',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->user)->get('/transactions/export?type=income');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Target Income', $content);
        $this->assertStringNotContainsString('Target Expense', $content);
        $this->assertStringContainsString('Tipe: income', $content);
    }
}
