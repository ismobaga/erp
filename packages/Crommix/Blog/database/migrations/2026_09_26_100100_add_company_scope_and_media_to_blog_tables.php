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
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->after('author_id')->constrained('blog_categories')->nullOnDelete();
            $table->string('cover_image_path')->nullable()->after('excerpt');
            $table->string('cover_image_alt')->nullable()->after('cover_image_path');
            $table->boolean('is_featured')->default(false)->after('status');
        });

        Schema::table('blog_pages', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
        });

        // Existing content belonged to the single public company: attach it to
        // the first active company (or the first company at all).
        $companyId = DB::table('companies')->where('is_active', true)->orderBy('id')->value('id')
            ?? DB::table('companies')->orderBy('id')->value('id');

        if ($companyId !== null) {
            DB::table('blog_posts')->whereNull('company_id')->update(['company_id' => $companyId]);
            DB::table('blog_pages')->whereNull('company_id')->update(['company_id' => $companyId]);
        }

        // Slugs are now unique per company instead of globally.
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status', 'published_at']);
        });

        Schema::table('blog_pages', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_pages', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'published_at']);
            $table->dropUnique(['company_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['cover_image_path', 'cover_image_alt', 'is_featured']);
        });
    }
};
