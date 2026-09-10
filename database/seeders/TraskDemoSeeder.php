<?php

namespace Database\Seeders;

use App\Models\Capital;
use App\Models\Product;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsappLink;
use App\Models\WhatsappLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TraskDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Owner & Team
        $owner = User::create([
            'name' => 'Budi Santoso (Owner)',
            'email' => 'owner@trask.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'phone' => '+628111222333',
        ]);

        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => 'Kedai Kita',
            'invite_code' => 'KEDAI-8X2K',
            'description' => 'Warung kopi & makanan ringan',
        ]);

        $owner->update(['current_team_id' => $team->id]);

        // Assign owner to team
        $owner->teams()->attach($team->id, ['role' => 'owner']);

        // 2. Create other users (Admin, Kasir)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@trask.test',
            'password' => Hash::make('password'),
            'current_team_id' => $team->id,
            'phone' => '+6281234567890',
        ]);
        $admin->teams()->attach($team->id, ['role' => 'admin']);

        $kasir1 = User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@trask.test',
            'password' => Hash::make('password'),
            'current_team_id' => $team->id,
            'phone' => '+6281112223334',
        ]);
        $kasir1->teams()->attach($team->id, ['role' => 'member']);

        $kasir2 = User::create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@trask.test',
            'password' => Hash::make('password'),
            'current_team_id' => $team->id,
            'phone' => '+6289876543210',
        ]);
        $kasir2->teams()->attach($team->id, ['role' => 'member']);

        // 3. Create Team Invitations
        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'kasir2@trask.test', // Existing user for demo
            'role' => 'member',
            'token' => Str::random(10),
        ]);

        // 4. Create Initial Capital & Fixed Expenses
        Capital::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'type' => 'awal',
            'amount' => 5000000.00,
            'description' => 'Setoran awal Owner Budi',
        ]);
        Capital::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'type' => 'tetap',
            'amount' => 500000.00, // Sewa
            'description' => 'Sewa tempat (September)',
        ]);
        Capital::create([
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'type' => 'tetap',
            'amount' => 125000.00, // Gaji
            'description' => 'Gaji Kasir Part-time',
        ]);

        // 5. Create Products
        $productKopiSusu = Product::create([
            'team_id' => $team->id, 'sku' => 'KS-01', 'name' => 'Kopi Susu Gula Aren', 'description' => 'Kopi arabika + gula aren + susu segar',
            'stock_initial' => 50, 'stock_current' => 5, 'stock_minimum' => 20, 'capital_price' => 4000.00, 'selling_price' => 15000.00, 'category' => 'minuman',
        ]);
        $productGula = Product::create([
            'team_id' => $team->id, 'sku' => 'GP-01', 'name' => 'Gula Pasir (5kg)', 'description' => 'Gula pasir curah',
            'stock_initial' => 25, 'stock_current' => 12, 'stock_minimum' => 25, 'capital_price' => 40.00, 'selling_price' => 0.00, 'category' => 'bahanbaku',
        ]);
        $productEsTeh = Product::create([
            'team_id' => $team->id, 'sku' => 'ET-01', 'name' => 'Es Teh Pouch', 'description' => 'Teh melati kemasan pouch',
            'stock_initial' => 30, 'stock_current' => 18, 'stock_minimum' => 30, 'capital_price' => 1000.00, 'selling_price' => 4000.00, 'category' => 'minuman',
        ]);
        $productCup = Product::create([
            'team_id' => $team->id, 'sku' => 'KC-01', 'name' => 'Kemasan Cup 12oz', 'description' => 'Cup plastik + tutup',
            'stock_initial' => 100, 'stock_current' => 45, 'stock_minimum' => 100, 'capital_price' => 250.00, 'selling_price' => 0.00, 'category' => 'kemasan',
        ]);

        // 6. Create Transactions (Demo Data)
        // Income from selling Kopi Susu (via WA)
        $tx1 = Transaction::create([
            'team_id' => $team->id, 'user_id' => $kasir1->id, 'type' => 'income', 'amount' => 180000.00,
            'description' => 'Kopi Susu Gula Aren x12', 'category' => 'penjualan', 'source' => 'whatsapp', 'whatsapp_sender' => '+628123456789',
        ]);
        $tx1->items()->create(['product_id' => $productKopiSusu->id, 'quantity' => 12, 'unit_price' => 15000.00, 'subtotal' => 180000.00]);
        $productKopiSusu->decrement('stock_current', 12);

        // Expense for buying Gula Pasir
        $tx2 = Transaction::create([
            'team_id' => $team->id, 'user_id' => $kasir1->id, 'type' => 'expense', 'amount' => 75000.00,
            'description' => 'Beli Gula Pasir 5kg + Es Batu', 'category' => 'bahanbaku', 'source' => 'web',
        ]);
        // No items for expense usually
        $productGula->decrement('stock_current', 1); // Assume 1 unit for 5kg bag

        // Income from selling Es Teh (via WA)
        $tx3 = Transaction::create([
            'team_id' => $team->id, 'user_id' => $kasir2->id, 'type' => 'income', 'amount' => 32000.00,
            'description' => 'Es Teh x8', 'category' => 'penjualan', 'source' => 'whatsapp', 'whatsapp_sender' => '+628987654321',
        ]);
        $tx3->items()->create(['product_id' => $productEsTeh->id, 'quantity' => 8, 'unit_price' => 4000.00, 'subtotal' => 32000.00]);
        $productEsTeh->decrement('stock_current', 8);

        // Expense for electricity bill
        $tx4 = Transaction::create([
            'team_id' => $team->id, 'user_id' => $owner->id, 'type' => 'expense', 'amount' => 125000.00,
            'description' => 'Bayar Listrik', 'category' => 'operasional', 'source' => 'web',
        ]);

        // Transaction for manual restock (add stock, no financial impact)
        $tx5 = Transaction::create([
            'team_id' => $team->id, 'user_id' => $admin->id, 'type' => 'income', 'amount' => 0.00,
            'description' => 'Restock Kemasan Cup', 'category' => 'stok', 'source' => 'web',
        ]);
        $tx5->items()->create(['product_id' => $productCup->id, 'quantity' => 50, 'unit_price' => 250.00, 'subtotal' => 12500.00]); // Note: subtotal here is not revenue, but cost for stock added
        $productCup->increment('stock_current', 50);

        // 7. Create Whatsapp Link for the team
        WhatsappLink::create([
            'team_id' => $team->id,
            'phone_number' => '+6281234567890', // Linked to Admin User
            'api_key' => 'fake-fonnte-api-key-12345',
            'provider' => 'fonnte',
            'webhook_token' => 'trask-webhook-secret-token',
            'is_active' => true,
        ]);

        // Link Team to the Whatsapp Link (many-to-many)
        $team->whatsappLinks()->attach(WhatsappLink::where('phone_number', '+6281234567890')->first()->id);

        // 8. Create Whatsapp Log entry (demo)
        WhatsappLog::create([
            'team_id' => $team->id,
            'whatsapp_link_id' => WhatsappLink::where('phone_number', '+6281234567890')->first()->id,
            'message_id' => 'message_abc123',
            'type' => 'outgoing',
            'sender_number' => '+6281234567890',
            'recipient_number' => '+6281112223334', // Kasir 1
            'message' => 'masuk 50rb kopi susu #penjualan', // Simulating a sent message
            'status' => 'sent',
            'sent_at' => now()->subMinutes(2),
        ]);
        WhatsappLog::create([
            'team_id' => $team->id,
            'whatsapp_link_id' => WhatsappLink::where('phone_number', '+6281234567890')->first()->id,
            'message_id' => 'message_def456',
            'type' => 'incoming',
            'sender_number' => '+6281112223334', // Kasir 1
            'recipient_number' => '+6281234567890',
            'message' => 'masuk 50rb kopi susu #penjualan', // Simulating received message
            'status' => 'read',
            'sent_at' => now()->subMinutes(1),
            'delivered_at' => now()->subMinute(),
            'read_at' => now(),
        ]);
    }
}
