<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            // Maturity of the project a Labs post describes: experimental, beta, available.
            $table->string('stage', 20)->nullable()->after('is_featured');
        });

        // Every company gets a "Labs" category for experiments and upcoming products.
        $now = now();

        DB::table('companies')->pluck('id')->each(function (int $companyId) use ($now): void {
            $exists = DB::table('blog_categories')
                ->where('company_id', $companyId)
                ->where('slug', 'labs')
                ->exists();

            if (! $exists) {
                DB::table('blog_categories')->insert([
                    'company_id' => $companyId,
                    'name' => 'Labs',
                    'slug' => 'labs',
                    'description' => 'Nos expérimentations : prototypes, modèles et outils en cours de développement. Certains deviendront des produits.',
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn('stage');
        });
    }
};
