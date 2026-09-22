<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\TelegramLog;
use App\Services\TelegramBotEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TelegramWebhookController extends Controller
{
    public function index(Request $request): View
    {
        $team = $request->user()->currentTeam;
        $bot = $team?->telegramBot()->first();
        $webhookUrl = url('/api/webhooks/telegram').($bot?->webhook_token ? '?token='.$bot->webhook_token : '');

        return view('telegram.index', [
            'bot' => $bot,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    public function updateLink(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $bot = $team->telegramBot()->first();

        $validated = $request->validate([
            'bot_username' => 'required|string|max:255|unique:telegram_bots,bot_username'.($bot ? ','.$bot->id : ''),
            'bot_token' => 'required|string|max:255',
            'webhook_token' => 'nullable|string|max:64',
        ]);

        if (empty($validated['webhook_token'])) {
            $validated['webhook_token'] = Str::random(32);
        }

        if ($bot) {
            $bot->update($validated);
        } else {
            $bot = $team->telegramBot()->create($validated + ['is_active' => true]);
            $team->telegramBots()->syncWithoutDetaching([$bot->id]);
        }

        return redirect()->route('telegram.index')->with('success', 'Pengaturan Bot Telegram disimpan.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $bot = $team->telegramBot()->where('is_active', true)->first();
        if (! $bot) {
            return response()->json([
                'connected' => false,
                'message' => 'Simpan konfigurasi Bot Telegram terlebih dahulu.',
            ], 422);
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->get("https://api.telegram.org/bot{$bot->bot_token}/getMe");

            $data = $response->json();
            $connected = $response->successful() && ($data['ok'] ?? false) === true;

            if ($connected && isset($data['result']['username'])) {
                $bot->update(['bot_username' => $data['result']['username']]);
            }

            return response()->json([
                'connected' => $connected,
                'message' => $connected
                    ? "Bot Telegram @{$bot->bot_username} terhubung!"
                    : ($data['description'] ?? 'Token Bot Telegram tidak valid.'),
                'bot' => $data['result'] ?? null,
            ], $connected ? 200 : 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'connected' => false,
                'message' => 'Tidak dapat menghubungi Telegram API. Periksa koneksi internet server.',
            ], 502);
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $token = $request->query('token') ?? $request->header('X-Telegram-Bot-Api-Secret-Token');
        if (empty($token)) {
            return response()->json(['message' => 'Webhook token is required.'], 401);
        }

        $bot = TelegramBot::where('webhook_token', $token)->where('is_active', true)->first();
        if (! $bot) {
            return response()->json(['message' => 'Invalid or inactive bot token.'], 401);
        }

        $team = $bot->team;
        if (! $team) {
            return response()->json(['message' => 'Team not found for this bot.'], 404);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (! $message || ! isset($message['text'])) {
            return response()->json(['message' => 'No text message to process.'], 200);
        }

        $messageId = (string) ($message['message_id'] ?? '');
        $incomingText = (string) $message['text'];
        $senderId = (string) ($message['from']['id'] ?? '');
        $senderName = $message['from']['first_name'] ?? ($message['from']['username'] ?? 'User');
        $chatId = (string) ($message['chat']['id'] ?? $senderId);

        // De-duplicate incoming message
        $duplicate = $messageId !== '' && $team->telegramLogs()
            ->where('telegram_bot_id', $bot->id)
            ->where('message_id', $messageId)
            ->where('type', 'incoming')
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'Message already processed.'], 200);
        }

        $team->telegramLogs()->create([
            'telegram_bot_id' => $bot->id,
            'message_id' => $messageId,
            'type' => 'incoming',
            'sender_id' => $senderId ?: $senderName,
            'recipient_id' => $bot->bot_username,
            'message' => $incomingText,
            'status' => 'received',
        ]);

        $replyMessage = (new TelegramBotEngine($team, $senderId))->processCommand($incomingText);

        $outgoingLog = $team->telegramLogs()->create([
            'telegram_bot_id' => $bot->id,
            'type' => 'outgoing',
            'sender_id' => $bot->bot_username,
            'recipient_id' => $chatId,
            'message' => $replyMessage,
            'status' => 'pending',
        ]);

        $sent = $this->sendReply($bot, $chatId, $replyMessage);
        $outgoingLog->update([
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
        ]);

        return response()->json(['reply' => $replyMessage, 'sent' => $sent]);
    }

    public function history(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 404);

        $logs = $team->telegramLogs()
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(fn (TelegramLog $log): array => [
                'sender' => $log->type === 'incoming' ? 'user' : 'bot',
                'text' => $log->message,
                'time' => $log->created_at?->format('H:i'),
            ]);

        $bot = $team->telegramBot()->first();

        return response()->json([
            'user' => $request->user()->name,
            'bot_username' => $bot?->bot_username,
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

        $bot = $team->telegramBot()->where('is_active', true)->first();
        abort_unless($bot, 422, 'Hubungkan Bot Telegram tim terlebih dahulu.');

        $sender = $user->phone ?? 'web:'.$user->id;
        $incomingMessage = $validated['message'];

        $team->telegramLogs()->create([
            'telegram_bot_id' => $bot->id,
            'type' => 'incoming',
            'sender_id' => $sender,
            'recipient_id' => $bot->bot_username,
            'message' => $incomingMessage,
            'status' => 'received',
        ]);

        $replyMessage = (new TelegramBotEngine($team->fresh(), $sender))->processCommand($incomingMessage);

        $team->telegramLogs()->create([
            'telegram_bot_id' => $bot->id,
            'type' => 'outgoing',
            'sender_id' => $bot->bot_username,
            'recipient_id' => $sender,
            'message' => $replyMessage,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json(['reply' => $replyMessage]);
    }

    protected function sendReply(TelegramBot $bot, string $chatId, string $message): bool
    {
        if (trim($bot->bot_token) === '' || trim($bot->bot_token) === 'local-dev-key') {
            return true;
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$bot->bot_token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);

            return $response->successful() && ($response->json('ok') ?? false) === true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
