<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\TaxProfileResolver;
use Carbon\CarbonInterface;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

#[Fillable([
    'company_id', // scoped by HasCompanyScope but still needs to be fillable for proper mass assignment in factories and imports
    'type',
    'company_name',
    'contact_name',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'notes',
    'status',
    'portal_token',
    'portal_token_hash',
    'portal_token_expires_at',
    'portal_token_last_used_at',
    'portal_token_revoked_at',
    'created_by',
    'updated_by',
])]
class Client extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'portal_token_expires_at' => 'datetime',
            'portal_token_last_used_at' => 'datetime',
            'portal_token_revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client): void {
            if (blank($client->portal_token_hash)) {
                $client->setPortalTokenAttributes(
                    plainToken: Str::random(64),
                    expiresAt: Carbon::now()->addDays((int) config('erp.portal.token_ttl_days', 180)),
                );
            }
        });
    }

    protected function portalToken(): Attribute
    {
        return Attribute::make(
            get: static function ($value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString((string) $value);
                } catch (\Throwable) {
                    return (string) $value;
                }
            },
            set: static function ($value): ?string {
                if (blank($value)) {
                    return null;
                }

                return Crypt::encryptString((string) $value);
            },
        );
    }

    public function portalUrl(): string
    {
        return route('portal.index', ['token' => $this->ensurePlainPortalToken()]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function portalTickets(): HasMany
    {
        return $this->hasMany(PortalTicket::class);
    }

    public function whatsappConversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function totalBalance(): float
    {
        // Use pre-loaded withSum aggregate to avoid N+1 in list views.
        if (array_key_exists('invoices_sum_balance_due', $this->getAttributes())) {
            return (float) $this->getAttribute('invoices_sum_balance_due');
        }

        return (float) $this->invoices()->sum('balance_due');
    }

    public function totalPaid(): float
    {
        if (array_key_exists('payments_sum_amount', $this->getAttributes())) {
            return (float) $this->getAttribute('payments_sum_amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function taxProfile(): array
    {
        return app(TaxProfileResolver::class)->resolveForClient($this);
    }

    /**
     * Rotate the portal token, invalidating any previously shared portal links.
     */
    public function regeneratePortalToken(): void
    {
        $this->issuePortalToken();
    }

    /**
     * @return string Plain token to share with the client.
     */
    public function issuePortalToken(?CarbonInterface $expiresAt = null): string
    {
        $plain = Str::random(64);

        $this->setPortalTokenAttributes(
            plainToken: $plain,
            expiresAt: $expiresAt ?? Carbon::now()->addDays((int) config('erp.portal.token_ttl_days', 180)),
        );

        $this->save();

        return $plain;
    }

    public function revokePortalToken(): void
    {
        $this->forceFill([
            'portal_token_revoked_at' => now(),
        ])->save();
    }

    public function ensurePlainPortalToken(): string
    {
        $plain = $this->portal_token;

        if (filled($plain) && blank($this->portal_token_revoked_at) && ($this->portal_token_expires_at === null || $this->portal_token_expires_at->isFuture())) {
            return (string) $plain;
        }

        return $this->issuePortalToken();
    }

    private function setPortalTokenAttributes(string $plainToken, CarbonInterface $expiresAt): void
    {
        $this->forceFill([
            'portal_token' => $plainToken,
            'portal_token_hash' => hash('sha256', $plainToken),
            'portal_token_expires_at' => Carbon::instance($expiresAt),
            'portal_token_last_used_at' => null,
            'portal_token_revoked_at' => null,
        ]);
    }
}
