<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'message_id',
    'direction',
    'event_type',
    'type',
    'body',
    'media_url',
    'from_jid',
    'ack_status',
    'delivered_at',
    'read_at',
    'raw_payload',
    'sent_at',
])]
class WhatsappMessage extends Model
{
    protected function casts(): array
    {
        return [
            'raw_payload'  => 'array',
            'sent_at'      => 'datetime',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }
}
