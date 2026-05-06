<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds SaaS and white-label columns to the companies table.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // White-label branding
            $table->string('custom_domain')->nullable()->after('whatsapp_enabled');
            $table->string('brand_primary_color', 20)->nullable()->after('custom_domain');
            $table->string('brand_secondary_color', 20)->nullable()->after('brand_primary_color');
            $table->string('white_label_logo_path')->nullable()->after('brand_secondary_color');
            $table->string('white_label_favicon_path')->nullable()->after('white_label_logo_path');
            $table->string('white_label_app_name')->nullable()->after('white_label_favicon_path');

            // Subscription / onboarding
            $table->timestamp('onboarded_at')->nullable()->after('white_label_app_name');
            $table->timestamp('trial_ends_at')->nullable()->after('onboarded_at');
            // Denormalised subscription status for fast read access.
            $table->string('subscription_status', 30)->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'custom_domain',
                'brand_primary_color',
                'brand_secondary_color',
                'white_label_logo_path',
                'white_label_favicon_path',
                'white_label_app_name',
                'onboarded_at',
                'trial_ends_at',
                'subscription_status',
            ]);
        });
    }
};
