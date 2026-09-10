<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'whatsapp_link_id',
        'message_id',
        'type', // 'incoming', 'outgoing'
        'sender_number',
        'recipient_number',
        'message',
        'status', // 'sent', 'delivered', 'read', 'failed'
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function whatsappLink(): BelongsTo
    {
        return $this->belongsTo(WhatsappLink::class);
    }
}
