<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappWebChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Team, 2: WhatsappLink}
     */
    protected function makeTeam(string $apiKey = 'local-dev-key'): array
    {
        $user = User::create([
            'name' => 'Andreas',
            'email' => 'andre@gmail.com',
            'password' => 'password',
            'phone' => '+6283872147483',
        ]);

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => 'Tim andreas',
            'invite_code' => 'MZFLP5OM',
        ]);

        $user->update(['current_team_id' => $team->id]);
        $user->teams()->attach($team->id, ['role' => 'owner']);

        $link = WhatsappLink::create([
            'team_id' => $team->id,
            'phone_number' => '+6281234567890',
            'api_key' => $apiKey,
            'provider' => 'fonnte',
            'webhook_token' => 'trask-webhook-secret-token',
            'is_active' => true,
        ]);

        return [$user, $team, $link];
    }

    public function test_send_from_web_records_transaction_and_logs(): void
    {
        [$user, $team, $link] = $this->makeTeam();

        $response = $this->actingAs($user)->postJson('/whatsapp/send', [
            'message' => 'masuk 50rb kopi #penjualan',
        ]);

        $response->assertOk();
        $response->assertJsonPath('reply', fn ($reply) => str_contains($reply, 'Pemasukan Berhasil'));

        $this->assertDatabaseHas('transactions', [
            'team_id' => $team->id,
            'type' => 'income',
            'amount' => 50000,
            'source' => 'whatsapp',
            'whatsapp_sender' => '+6283872147483',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'team_id' => $team->id,
            'whatsapp_link_id' => $link->id,
            'type' => 'incoming',
        ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'team_id' => $team->id,
            'whatsapp_link_id' => $link->id,
            'type' => 'outgoing',
        ]);
    }

    public function test_provider_webhook_accepts_fonnte_payload(): void
    {
        [$user, $team, $link] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);

        $response = $this->postJson(
            '/api/webhooks/whatsapp?token=trask-webhook-secret-token',
            [
                'device' => '+6281234567890',
                'sender' => '083872147483',
                'message' => 'keluar 25k es batu #bahanbaku',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('sent', true);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '083872147483'
            && str_contains($request['message'], 'Pengeluaran Berhasil'));

        $this->assertDatabaseHas('transactions', [
            'team_id' => $team->id,
            'type' => 'expense',
            'amount' => 25000,
            'source' => 'whatsapp',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'team_id' => $team->id,
            'whatsapp_link_id' => $link->id,
            'type' => 'outgoing',
            'status' => 'sent',
        ]);
    }

    public function test_provider_webhook_accepts_nested_payload_and_ignores_duplicates(): void
    {
        [$user, $team, $link] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);

        $payload = [
            'data' => [
                'id' => 'message-123',
                'from' => $user->phone,
                'body' => 'masuk 10rb kopi',
                'receiver' => $link->phone_number,
            ],
        ];

        $this->postJson('/api/webhooks/whatsapp?token='.$link->webhook_token, $payload)->assertOk();
        $duplicateResponse = $this->postJson('/api/webhooks/whatsapp?token='.$link->webhook_token, $payload);

        $duplicateResponse->assertOk()->assertJsonPath('duplicate', true);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('whatsapp_logs', 2);
    }

    public function test_provider_webhook_requires_a_token(): void
    {
        [, $team] = $this->makeTeam();

        $response = $this->postJson('/api/webhooks/whatsapp', [
            'sender' => '+6283872147483',
            'message' => 'cek saldo',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('whatsapp_logs', 0);
    }

    public function test_provider_webhook_ignores_sender_outside_the_team(): void
    {
        [$user, $team, $link] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::preventStrayRequests();

        $response = $this->postJson('/api/webhooks/whatsapp?token='.$link->webhook_token, [
            'device' => $link->phone_number,
            'sender' => '+6289999999999',
            'message' => 'help',
            'message_id' => 'outsider-message-1',
        ]);

        $response->assertOk()->assertJsonPath('ignored', true);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('whatsapp_logs', [
            'team_id' => $team->id,
            'type' => 'incoming',
            'sender_number' => '+6289999999999',
            'status' => 'ignored',
        ]);
        $this->assertDatabaseMissing('whatsapp_logs', ['type' => 'outgoing']);
    }

    public function test_provider_failure_marks_reply_as_failed(): void
    {
        [$user, $team, $link] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false])]);

        $response = $this->postJson('/api/webhooks/whatsapp?token='.$link->webhook_token, [
            'device' => $link->phone_number,
            'sender' => $user->phone,
            'message' => 'cek saldo',
        ]);

        $response->assertOk()->assertJsonPath('sent', false);
        $this->assertDatabaseHas('whatsapp_logs', [
            'team_id' => $team->id,
            'type' => 'outgoing',
            'status' => 'failed',
        ]);
    }

    public function test_connection_reports_a_disconnected_device_as_not_connected(): void
    {
        [$user] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::fake([
            'api.fonnte.com/device' => Http::response([
                'status' => true,
                'device' => '6281234567890',
                'device_status' => 'disconnect',
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/whatsapp/test-connection');

        $response->assertUnprocessable()
            ->assertJsonPath('connected', false)
            ->assertJsonPath('message', 'Token valid, tetapi device WhatsApp sedang disconnect di Fonnte.');
    }

    public function test_history_returns_linked_numbers_and_logs(): void
    {
        [$user] = $this->makeTeam();

        $this->actingAs($user)->postJson('/whatsapp/send', [
            'message' => 'cek saldo',
        ])->assertOk();

        $response = $this->actingAs($user)->getJson('/whatsapp/history');

        $response->assertOk();
        $response->assertJsonPath('phone', '+6283872147483');
        $response->assertJsonPath('bot_number', '+6281234567890');
        $response->assertJsonCount(2, 'messages');
    }

    public function test_whatsapp_page_shows_tokenized_webhook_url(): void
    {
        [$user] = $this->makeTeam();

        $response = $this->actingAs($user)->get('/whatsapp');

        $response->assertOk();
        $response->assertSee('/api/webhooks/whatsapp?token=trask-webhook-secret-token', false);
    }

    public function test_update_link_saves_gateway_settings(): void
    {
        [$user, $team] = $this->makeTeam();

        $response = $this->actingAs($user)->patch('/whatsapp', [
            'provider' => 'fonnte',
            'phone_number' => '+6281234567890',
            'api_key' => 'real-fonnte-token',
            'webhook_token' => 'token-baru-123',
        ]);

        $response->assertRedirect(route('whatsapp.index'));

        $this->assertDatabaseHas('whatsapp_links', [
            'team_id' => $team->id,
            'provider' => 'fonnte',
            'api_key' => 'real-fonnte-token',
            'webhook_token' => 'token-baru-123',
        ]);
    }

    public function test_connection_reports_a_valid_fonnte_device(): void
    {
        [$user] = $this->makeTeam(apiKey: 'real-fonnte-token');
        Http::fake([
            'api.fonnte.com/device' => Http::response([
                'status' => true,
                'device' => '6281234567890',
                'device_status' => 'connect',
                'quota' => '100',
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/whatsapp/test-connection');

        $response->assertOk()
            ->assertJsonPath('connected', true)
            ->assertJsonPath('device_status', 'connect')
            ->assertJsonPath('quota', '100');
    }

    public function test_connection_reports_an_invalid_fonnte_token(): void
    {
        [$user] = $this->makeTeam(apiKey: 'invalid-fonnte-token');
        Http::fake([
            'api.fonnte.com/device' => Http::response([
                'status' => false,
                'reason' => 'token invalid',
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/whatsapp/test-connection');

        $response->assertUnprocessable()
            ->assertJsonPath('connected', false)
            ->assertJsonPath('message', 'token invalid');
    }
}
