<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'telegram_bot_id',
        'message_id',
        'type', // 'incoming', 'outgoing'
        'sender_id',
        'recipient_id',
        'message',
        'status', // 'sent', 'delivered', 'read', 'failed'
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class);
    }
}
