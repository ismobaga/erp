<?php

namespace Crommix\SaaS\Contracts;

use Crommix\SaaS\Models\TenantSubscription;

/**
 * Contract for entities that carry a SaaS subscription (typically Company).
 */
interface HasSubscription
{
    /**
     * Return the active or trialing subscription, or null if none exists.
     */
    public function activeSubscription(): ?TenantSubscription;

    /**
     * Whether the entity currently has an active or trialing subscription.
     */
    public function isSubscribed(): bool;

    /**
     * Whether the entity is in a free-trial period.
     */
    public function isOnTrial(): bool;

    /**
     * Whether the feature key is enabled for this entity's plan + overrides.
     */
    public function hasFeature(string $feature): bool;
}
