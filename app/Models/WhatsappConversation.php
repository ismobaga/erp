<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'assigned_to',
    'chat_id',
    'contact_name',
    'status',
    'last_message_at',
])]
class WhatsappConversation extends Model
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id')->orderBy('sent_at');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(WhatsappMessageLog::class, 'conversation_id');
    }

    /**
     * Return a human-readable display name for this conversation.
     */
    public function displayName(): string
    {
        if ($this->client) {
            return $this->client->company_name ?: ($this->client->contact_name ?: $this->chat_id);
        }

        return $this->contact_name ?: $this->chat_id;
    }
}
