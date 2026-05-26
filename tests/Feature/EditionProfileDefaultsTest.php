<?php

namespace Tests\Feature;

use Tests\TestCase;

class EditionProfileDefaultsTest extends TestCase
{
    public function test_simple_profile_defaults_match_business_first_sidebar(): void
    {
        $this->assertSame([
            'dashboard',
            'clients',
            'projects',
            'invoices',
            'payments',
            'expenses',
            'reports',
            'settings',
        ], config('erp.edition.profiles.simple.enabled_modules'));
    }

    public function test_growing_profile_defaults_unlock_progressive_modules(): void
    {
        $this->assertSame([
            'dashboard',
            'clients',
            'projects',
            'quotes',
            'invoices',
            'payments',
            'expenses',
            'reports',
            'settings',
        ], config('erp.edition.profiles.growing.enabled_modules'));
    }
}
