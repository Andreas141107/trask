<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->team = Team::create([
            'owner_id' => $this->user->id,
            'name' => 'Tim Live Filter',
            'invite_code' => 'LIVE1234',
        ]);

        $this->user->update(['current_team_id' => $this->team->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'owner']);
    }

    public function test_transaction_index_keeps_filter_state_in_view(): void
    {
        Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 50000,
            'description' => 'Kopi Live',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->user)->get('/transactions?type=income&search=Kopi');

        $response->assertOk();
        $response->assertSee('id="transaction-list-wrap"', false);
        $response->assertSee('data-live-filter', false);
        $response->assertSee('Kopi Live');
    }

    public function test_product_index_keeps_filter_state_in_view(): void
    {
        Product::create([
            'team_id' => $this->team->id,
            'sku' => 'LV-01',
            'name' => 'Produk Live',
            'stock_initial' => 5,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'capital_price' => 1000,
            'selling_price' => 3000,
            'category' => 'minuman',
        ]);

        $response = $this->actingAs($this->user)->get('/products?category=minuman&search=Live');

        $response->assertOk();
        $response->assertSee('id="product-list-wrap"', false);
        $response->assertSee('data-live-filter', false);
        $response->assertSee('Produk Live');
    }
}
