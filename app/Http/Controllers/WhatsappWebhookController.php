<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\WhatsappLink;
use App\Models\WhatsappLog;
use App\Services\WhatsappBotEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappWebhookController extends Controller
{
    public function index(Request $request): View
    {
        $team = $request->user()->currentTeam;
        $link = $team?->whatsappLink()->first();
        $webhookUrl = url('/api/webhooks/whatsapp').($link?->webhook_token ? '?token='.$link->webhook_token : '');

        return view('whatsapp.index', [
            'link' => $link,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    public function updateLink(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $link = $team->whatsappLink()->first();

        $validated = $request->validate([
            'provider' => 'required|in:fonnte,wablas',
            'phone_number' => 'required|string|max:20|unique:whatsapp_links,phone_number'.($link ? ','.$link->id : ''),
            'api_key' => 'required|string|max:255',
            'webhook_token' => 'nullable|string|max:64',
        ]);

        if (empty($validated['webhook_token'])) {
            $validated['webhook_token'] = Str::random(32);
        }

        if ($link) {
            $link->update($validated);
        } else {
            $link = $team->whatsappLink()->create($validated + ['is_active' => true]);
            $team->whatsappLinks()->syncWithoutDetaching([$link->id]);
        }

        return redirect()->route('whatsapp.index')->with('success', 'Pengaturan WhatsApp disimpan.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $link = $team->whatsappLink()->where('is_active', true)->first();
        if (! $link) {
            return response()->json([
                'connected' => false,
                'message' => 'Simpan konfigurasi WhatsApp terlebih dahulu.',
            ], 422);
        }

        if ($link->provider !== 'fonnte') {
            return response()->json([
                'connected' => false,
                'message' => 'Test connection saat ini tersedia untuk Fonnte.',
            ], 422);
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withHeaders(['Authorization' => $link->api_key])
                ->post('https://api.fonnte.com/device');
            $providerData = $response->json();
            $connected = $response->successful()
                && $this->providerResponseSucceeded($providerData)
                && ($providerData['device_status'] ?? null) === 'connect';

            return response()->json([
                'connected' => $connected,
                'message' => $connected
                    ? 'Fonnte terhubung dan device aktif.'
                    : (string) ($providerData['reason'] ?? (($providerData['device_status'] ?? null) === 'disconnect'
                        ? 'Token valid, tetapi device WhatsApp sedang disconnect di Fonnte.'
                        : 'Token Fonnte tidak valid atau device tidak aktif.')),
                'device_status' => $providerData['device_status'] ?? null,
                'device' => $providerData['device'] ?? $link->phone_number,
                'quota' => $providerData['quota'] ?? null,
            ], $connected ? 200 : 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'connected' => false,
                'message' => 'Tidak dapat menghubungi Fonnte. Periksa koneksi server.',
            ], 502);
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $token = $request->query('token') ?? $request->header('X-Webhook-Token');
        $provider = (string) ($request->header('X-Provider', 'fonnte') ?: 'fonnte');
        $payload = $request->all();
        $nestedPayload = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $messageId = $this->firstPayloadValue($payload, $nestedPayload, ['inboxid', 'message_id', 'id']);
        $incomingMessage = $this->firstPayloadValue($payload, $nestedPayload, ['message', 'body', 'text']);
        $senderNumber = $this->firstPayloadValue($payload, $nestedPayload, ['sender', 'from', 'phone']);
        $deviceNumber = $this->firstPayloadValue($payload, $nestedPayload, ['device', 'receiver', 'to']);

        if (empty($token)) {
            return response()->json(['message' => 'Webhook token is required.'], 401);
        }

        $link = $this->resolveLink($token, $provider, $deviceNumber);

        if (! $link) {
            return response()->json(['message' => 'Invalid provider or token.'], 401);
        }

        if (! $incomingMessage || ! $senderNumber) {
            return response()->json(['message' => 'No message to process.'], 200);
        }

        // Find the team associated with this WhatsApp link
        $team = $link->team;
        if (! $team) {
            return response()->json(['message' => 'Team not found for this whatsapp link.'], 404);
        }

        if (! $this->senderBelongsToTeam($team, (string) $senderNumber)) {
            $team->whatsappLogs()->create([
                'whatsapp_link_id' => $link->id,
                'message_id' => $messageId,
                'type' => 'incoming',
                'sender_number' => (string) $senderNumber,
                'recipient_number' => (string) ($deviceNumber ?? $link->phone_number),
                'message' => (string) $incomingMessage,
                'status' => 'ignored',
            ]);

            return response()->json(['message' => 'Sender is not a member of this team.', 'ignored' => true]);
        }

        // Log the incoming message
        $duplicate = $messageId
            ? $team->whatsappLogs()
                ->where('whatsapp_link_id', $link->id)
                ->where('message_id', (string) $messageId)
                ->where('type', 'incoming')
                ->exists()
            : false;

        if ($duplicate) {
            return response()->json(['message' => 'Message already processed.', 'duplicate' => true]);
        }

        $team->whatsappLogs()->create([
            'whatsapp_link_id' => $link->id,
            'message_id' => $messageId,
            'type' => 'incoming',
            'sender_number' => (string) $senderNumber,
            'recipient_number' => (string) ($deviceNumber ?? $link->phone_number),
            'message' => (string) $incomingMessage,
            'status' => 'received',
        ]);

        // Process the command using the bot engine
        $responseMessage = (new WhatsappBotEngine($team, (string) $senderNumber))->processCommand((string) $incomingMessage);

        $outgoingLog = $team->whatsappLogs()->create([
            'whatsapp_link_id' => $link->id,
            'type' => 'outgoing',
            'sender_number' => (string) $link->phone_number,
            'recipient_number' => (string) $senderNumber,
            'message' => $responseMessage,
            'status' => 'pending',
        ]);

        $sent = $this->sendReply($link, (string) $senderNumber, $responseMessage);
        $outgoingLog->update([
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
        ]);

        return response()->json(['reply' => $responseMessage, 'sent' => $sent]);
    }

    // Handle message status updates (optional)
    public function handleStatusUpdate(Request $request): JsonResponse
    {
        // Process status updates like 'delivered', 'read', 'failed'
        // Find log by message_id and update status/timestamps
        return response()->json(['message' => 'Status update received.']);
    }

    public function history(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $logs = $team->whatsappLogs()
            ->orderBy('created_at')
            ->limit(50)
            ->get(['type', 'message', 'created_at'])
            ->map(fn (WhatsappLog $log): array => [
                'sender' => $log->type === 'incoming' ? 'user' : 'bot',
                'text' => $log->message,
                'time' => $log->created_at?->format('H:i'),
            ]);

        $link = $team->whatsappLink()->first();

        return response()->json([
            'phone' => $request->user()->phone,
            'bot_number' => $link?->phone_number,
            'messages' => $logs,
        ]);
    }

    public function sendFromWeb(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        abort_unless($team, 404);

        $link = $team->whatsappLink()->where('is_active', true)->first();
        abort_unless($link, 422, 'Hubungkan nomor WhatsApp tim terlebih dahulu.');

        $sender = $user->phone ?? 'web:'.$user->id;
        $incomingMessage = $validated['message'];

        $team->whatsappLogs()->create([
            'whatsapp_link_id' => $link->id,
            'type' => 'incoming',
            'sender_number' => $sender,
            'recipient_number' => (string) $link->phone_number,
            'message' => $incomingMessage,
            'status' => 'received',
        ]);

        $responseMessage = (new WhatsappBotEngine($team->fresh(), $sender))->processCommand($incomingMessage);

        $team->whatsappLogs()->create([
            'whatsapp_link_id' => $link->id,
            'type' => 'outgoing',
            'sender_number' => (string) $link->phone_number,
            'recipient_number' => $sender,
            'message' => $responseMessage,
            'status' => 'sent',
        ]);

        return response()->json(['reply' => $responseMessage]);
    }

    protected function resolveLink(?string $token, string $provider, mixed $deviceNumber): ?WhatsappLink
    {
        $query = WhatsappLink::where('is_active', true);

        if (! empty($token)) {
            return (clone $query)->where('webhook_token', $token)->first();
        }

        if (! empty($deviceNumber)) {
            $digits = (string) preg_replace('/\D/', '', (string) $deviceNumber);
            $candidates = array_unique(array_filter([
                (string) $deviceNumber,
                $digits !== '' ? $digits : null,
                $digits !== '' ? '+'.$digits : null,
            ]));

            $byDevice = (clone $query)->whereIn('phone_number', $candidates)->first();
            if ($byDevice) {
                return $byDevice;
            }
        }

        return $query->where('provider', $provider)->first();
    }

    protected function sendReply(WhatsappLink $link, string $target, string $message): bool
    {
        if (trim($link->api_key) === '' || trim($link->api_key) === 'local-dev-key') {
            return true;
        }

        try {
            if ($link->provider === 'wablas') {
                $response = Http::connectTimeout(3)
                    ->timeout(10)
                    ->withToken($link->api_key)
                    ->post('https://console.wablas.com/api/v2/send-message', [
                        'data' => [['phone' => $target, 'message' => $message]],
                    ]);

                return $response->successful() && $this->providerResponseSucceeded($response->json());
            }

            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withHeaders(['Authorization' => $link->api_key])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            return $response->successful() && $this->providerResponseSucceeded($response->json());
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $nestedPayload
     * @param  list<string>  $keys
     */
    protected function firstPayloadValue(array $payload, array $nestedPayload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? $nestedPayload[$key] ?? null;

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function providerResponseSucceeded(mixed $response): bool
    {
        if (! is_array($response) || ! array_key_exists('status', $response)) {
            return true;
        }

        return in_array($response['status'], [true, 1, '1', 'true', 'success', 'SUCCESS'], true);
    }

    protected function senderBelongsToTeam(Team $team, string $senderNumber): bool
    {
        $senderDigits = $this->normalizePhoneNumber($senderNumber);

        return $senderDigits !== '' && $team->users->contains(function ($user) use ($senderDigits): bool {
            return $this->normalizePhoneNumber((string) $user->phone) === $senderDigits;
        });
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = (string) preg_replace('/\D/', '', $phoneNumber);

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return ltrim($digits, '+');
    }
}
