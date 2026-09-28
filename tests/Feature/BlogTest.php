<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Crommix\Blog\Filament\Resources\BlogCategories\BlogCategoryResource;
use Crommix\Blog\Filament\Resources\BlogPosts\BlogPostResource;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use Crommix\Blog\Models\BlogCategory;
use Crommix\Blog\Models\BlogPage;
use Crommix\Blog\Models\BlogPost;
use Crommix\Blog\Models\BlogTag;
use Crommix\Blog\Support\BlogContent;
use Crommix\Blog\Support\PublicBlogCompany;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // The base TestCase binds "Test Company", the first active company,
        // which is therefore the one served on the public site.
        $this->company = currentCompany();
        $this->enableBlog($this->company);
    }

    // ── Public site ─────────────────────────────────────────────────────────

    public function test_public_blog_returns_404_when_feature_disabled(): void
    {
        $this->company->update(['advanced_options' => ['blog' => false]]);

        $this->get('/blog')->assertNotFound();
        $this->get('/blog/feed.xml')->assertNotFound();
    }

    public function test_index_lists_only_published_posts_of_the_public_company(): void
    {
        $this->makePost(['title' => 'Article publié']);
        $this->makePost(['title' => 'Brouillon secret', 'status' => 'draft']);
        $this->makePost(['title' => 'Article programmé', 'published_at' => now()->addWeek()]);

        $other = $this->otherCompany();
        $this->makePost(['title' => 'Article autre société'], $other);

        $this->get('/blog')
            ->assertOk()
            ->assertSeeText('Article publié')
            ->assertDontSeeText('Brouillon secret')
            ->assertDontSeeText('Article programmé')
            ->assertDontSeeText('Article autre société');
    }

    public function test_rich_content_is_sanitized_and_legacy_text_is_escaped(): void
    {
        $rich = $this->makePost([
            'slug' => 'riche',
            'content' => '<h2>Titre interne</h2><p>Paragraphe <strong>gras</strong></p><script>alert(1)</script><img src="x" onerror="alert(2)">',
        ]);
        $legacy = $this->makePost([
            'slug' => 'ancien',
            'content' => "Ligne 1\nLigne 2 <b>pas du html</b>",
        ]);

        $html = $this->get('/blog/'.$rich->slug)->assertOk()->getContent();
        $this->assertStringContainsString('<h2>Titre interne</h2>', $html);
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onerror', $html);

        // Legacy textarea content is escaped verbatim with its line breaks kept.
        $this->assertSame("Ligne 1<br />\nLigne 2 &lt;b&gt;pas du html&lt;/b&gt;", (string) $legacy->renderedContent());

        $plain = BlogContent::toHtml("Ligne 1\nLigne 2 & plus");
        $this->assertSame("Ligne 1<br />\nLigne 2 &amp; plus", (string) $plain);
    }

    public function test_show_page_has_seo_and_social_meta(): void
    {
        $post = $this->makePost([
            'title' => 'Structurer sa trésorerie',
            'seo_description' => 'Description SEO précise',
            'cover_image_path' => 'blog/covers/cover.jpg',
        ]);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta name="description" content="Description SEO précise">', false)
            ->assertSee('<link rel="canonical" href="'.route('blog.show', $post->slug).'">', false)
            ->assertSee('blog/covers/cover.jpg', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('application/rss+xml', false);
    }

    public function test_same_slug_is_allowed_per_company_and_public_site_shows_its_own(): void
    {
        $this->makePost(['title' => 'Version publique', 'slug' => 'bienvenue']);
        $this->makePost(['title' => 'Version concurrente', 'slug' => 'bienvenue'], $this->otherCompany());

        $this->get('/blog/bienvenue')
            ->assertOk()
            ->assertSeeText('Version publique')
            ->assertDontSeeText('Version concurrente');
    }

    public function test_unpublished_or_foreign_post_returns_404(): void
    {
        $draft = $this->makePost(['status' => 'draft']);
        $foreign = $this->makePost(['slug' => 'etranger'], $this->otherCompany());

        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/'.$foreign->slug)->assertNotFound();
    }

    public function test_category_and_tag_pages_filter_posts(): void
    {
        $finance = BlogCategory::create(['name' => 'Finance']);
        $rh = BlogCategory::create(['name' => 'Ressources humaines']);
        $tag = BlogTag::create(['name' => 'Trésorerie']);

        $a = $this->makePost(['title' => 'Budget annuel', 'category_id' => $finance->id]);
        $a->tags()->attach($tag);
        $this->makePost(['title' => 'Recrutement', 'category_id' => $rh->id]);

        $this->assertSame('finance', $finance->slug);

        $this->get('/blog/categorie/finance')
            ->assertOk()
            ->assertSeeText('Budget annuel')
            ->assertDontSeeText('Recrutement');

        $this->get('/blog/tag/'.$tag->slug)
            ->assertOk()
            ->assertSeeText('Budget annuel')
            ->assertDontSeeText('Recrutement');

        $this->get('/blog/categorie/inconnue')->assertNotFound();
    }

    public function test_index_search_and_featured_post(): void
    {
        $this->makePost(['title' => 'Guide de la facturation']);
        $this->makePost(['title' => 'Stock et inventaire']);
        $this->makePost(['title' => 'Notre article phare', 'is_featured' => true]);

        $html = $this->get('/blog')->assertOk()->assertSeeText('À la une')->getContent();
        $this->assertSame(1, substr_count($html, 'Notre article phare'), 'Featured post must not be repeated in the grid.');
        $this->assertSame('Titre Un texte gras.', BlogContent::toText('<h2>Titre</h2><p>Un texte <strong>gras</strong>.</p><script>x()</script>'));

        $this->get('/blog?q=factur')
            ->assertOk()
            ->assertSeeText('Guide de la facturation')
            ->assertDontSeeText('Stock et inventaire')
            ->assertSee('noindex', false);
    }

    public function test_rss_feed_lists_published_posts(): void
    {
        $this->makePost(['title' => 'Premier & unique']);
        $this->makePost(['title' => 'Brouillon', 'status' => 'draft']);

        $response = $this->get('/blog/feed.xml')->assertOk();

        $this->assertStringStartsWith('application/rss+xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertCount(1, $xml->channel->item);
        $this->assertSame('Premier & unique', (string) $xml->channel->item[0]->title);
    }

    public function test_sitemap_lists_marketing_pages_and_blog_content(): void
    {
        $post = $this->makePost(['slug' => 'visible']);
        $this->makePost(['slug' => 'cache', 'status' => 'draft']);
        BlogPage::create(['title' => 'Offre', 'slug' => 'offre', 'content' => 'x', 'status' => 'published']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString(route('company.about'), $xml);
        $this->assertStringContainsString(route('blog.show', $post->slug), $xml);
        $this->assertStringContainsString(route('blog.pages.show', 'offre'), $xml);
        $this->assertStringNotContainsString('/blog/cache', $xml);

        $this->company->update(['advanced_options' => ['blog' => false]]);
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString(route('company.about'), $xml);
        $this->assertStringNotContainsString('/blog', $xml);
    }

    public function test_public_company_can_be_pinned_by_slug_or_matched_by_host(): void
    {
        $other = $this->otherCompany(['slug' => 'filiale', 'website' => 'https://www.filiale.example']);

        $this->assertTrue(PublicBlogCompany::resolve(Request::create('http://erp.test/blog'))->is($this->company));
        $this->assertTrue(PublicBlogCompany::resolve(Request::create('http://filiale.example/blog'))->is($other));

        config(['crommix-blog.public_company' => 'filiale']);
        $this->assertTrue(PublicBlogCompany::resolve(Request::create('http://erp.test/blog'))->is($other));
    }

    public function test_public_page_renders_rich_content(): void
    {
        BlogPage::create([
            'title' => 'À propos du cabinet',
            'slug' => 'cabinet',
            'content' => '<p>Nous <em>accompagnons</em> les PME.</p>',
            'status' => 'published',
        ]);

        $this->get('/pages/cabinet')->assertOk()->assertSee('<em>accompagnons</em>', false);
    }

    // ── Admin panel ─────────────────────────────────────────────────────────

    public function test_admin_resources_are_hidden_when_blog_feature_disabled(): void
    {
        $this->actingAs($this->admin());

        $this->assertTrue(BlogPostResource::canViewAny());
        $this->assertTrue(BlogCategoryResource::canViewAny());

        $this->company->update(['advanced_options' => ['blog' => false]]);
        $this->app->instance('currentCompany', $this->company->fresh());

        $this->assertFalse(BlogPostResource::canViewAny());
        $this->assertFalse(BlogCategoryResource::canViewAny());
    }

    public function test_admin_can_create_post_with_category_and_tags_scoped_to_company(): void
    {
        $user = $this->admin();
        Filament::setCurrentPanel(Filament::getPanel('blog'));

        $category = BlogCategory::create(['name' => 'Guides']);
        $tag = BlogTag::create(['name' => 'PME']);

        Livewire::actingAs($user)
            ->test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Mon nouvel article',
                'slug' => 'mon-nouvel-article',
                'content' => '<p>Bonjour</p>',
                'status' => 'published',
                'category_id' => $category->id,
                'tags' => [$tag->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::query()->where('slug', 'mon-nouvel-article')->firstOrFail();
        $this->assertSame($this->company->id, $post->company_id);
        $this->assertSame($user->id, $post->author_id);
        $this->assertTrue($post->category->is($category));
        $this->assertSame(['PME'], $post->tags->pluck('name')->all());
    }

    public function test_slug_uniqueness_is_per_company_in_admin_form(): void
    {
        $user = $this->admin();
        Filament::setCurrentPanel(Filament::getPanel('blog'));

        $this->makePost(['slug' => 'pris'], $this->otherCompany());

        Livewire::actingAs($user)
            ->test(CreateBlogPost::class)
            ->fillForm(['title' => 'A', 'slug' => 'pris', 'content' => '<p>x</p>', 'status' => 'draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::actingAs($user)
            ->test(CreateBlogPost::class)
            ->fillForm(['title' => 'B', 'slug' => 'pris', 'content' => '<p>x</p>', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_admin_pages_render(): void
    {
        $user = $this->admin();
        $post = $this->makePost();
        $this->makePost(['cover_image_path' => 'blog/covers/a.jpg']);
        BlogCategory::create(['name' => 'Guides']);
        BlogTag::create(['name' => 'PME']);

        foreach ([
            '/blog-admin/blog-posts',
            '/blog-admin/blog-posts/create',
            '/blog-admin/blog-posts/'.$post->id.'/edit',
            '/blog-admin/blog-pages',
            '/blog-admin/blog-pages/create',
            '/blog-admin/blog-categories',
            '/blog-admin/blog-tags',
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_list_only_shows_current_company_posts(): void
    {
        $user = $this->admin();
        Filament::setCurrentPanel(Filament::getPanel('blog'));

        $mine = $this->makePost(['title' => 'Le mien']);
        $theirs = $this->makePost(['title' => 'Le leur'], $this->otherCompany());

        Livewire::actingAs($user)
            ->test(ListBlogPosts::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    // ── Permissions ─────────────────────────────────────────────────────────

    public function test_blog_permissions_migration_creates_editor_role(): void
    {
        $editor = Role::query()->where('name', 'Editor')->firstOrFail();

        $this->assertTrue($editor->hasPermissionTo('blog.create'));
        $this->assertFalse($editor->hasPermissionTo('blog.publish'));
    }

    public function test_only_blog_roles_can_enter_the_blog_panel(): void
    {
        $this->actingAs($this->userWithRole('Editor'))->get('/blog-admin/blog-posts')->assertOk();
        $this->actingAs($this->userWithRole('Read Only'))->get('/blog-admin/blog-posts')->assertOk();
        $this->actingAs($this->userWithRole('Staff'))->get('/blog-admin/blog-posts')->assertForbidden();
        $this->actingAs($this->userWithRole('Finance'))->get('/blog-admin/blog-posts')->assertForbidden();
    }

    public function test_read_only_can_browse_but_not_write(): void
    {
        $this->actingAs($this->userWithRole('Read Only'));
        $draft = $this->makePost(['status' => 'draft']);

        $this->assertTrue(BlogPostResource::canViewAny());
        $this->assertFalse(BlogPostResource::canCreate());
        $this->assertFalse(BlogPostResource::canEdit($draft));
        $this->assertFalse(BlogCategoryResource::canCreate());
    }

    public function test_editor_writes_drafts_but_cannot_publish_or_touch_live_posts(): void
    {
        $editor = $this->userWithRole('Editor');
        $this->actingAs($editor);
        Filament::setCurrentPanel(Filament::getPanel('blog'));

        Livewire::actingAs($editor)
            ->test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Proposition de la rédaction',
                'slug' => 'proposition',
                'content' => '<p>Brouillon</p>',
                'status' => 'published',
                'is_featured' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::query()->where('slug', 'proposition')->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->assertFalse($post->is_featured);

        $this->assertTrue(BlogPostResource::canEdit($post));
        $this->assertFalse(BlogPostResource::canDelete($post));

        $live = $this->makePost();
        $this->assertFalse(BlogPostResource::canEdit($live));
        $this->assertFalse(BlogPostResource::canDelete($live));
        $this->assertFalse(BlogPostResource::canPublish());
    }

    public function test_admin_can_publish_and_delete(): void
    {
        $this->actingAs($this->admin());
        $live = $this->makePost();

        $this->assertTrue(BlogPostResource::canPublish());
        $this->assertTrue(BlogPostResource::canEdit($live));
        $this->assertTrue(BlogPostResource::canDelete($live));
    }

    public function test_main_site_blog_link_follows_the_public_blog_company(): void
    {
        $this->company->update(['advanced_options' => ['blog' => false]]);
        $this->otherCompany(['slug' => 'filiale']);

        $this->get('/about')->assertOk()->assertDontSee(route('blog.index'), false);

        config(['crommix-blog.public_company' => 'filiale']);
        $this->get('/about')->assertOk()->assertSee(route('blog.index'), false);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function enableBlog(Company $company): void
    {
        $company->update(['advanced_options' => array_merge((array) $company->advanced_options, ['blog' => true])]);
    }

    private function otherCompany(array $attributes = []): Company
    {
        $company = Company::create(array_merge([
            'name' => 'Autre Société',
            'currency' => 'FCFA',
            'is_active' => true,
        ], $attributes));
        $this->enableBlog($company);

        return $company;
    }

    private function makePost(array $attributes = [], ?Company $company = null): BlogPost
    {
        static $sequence = 0;
        $sequence++;

        return BlogPost::withoutCompanyScope()->create(array_merge([
            'company_id' => ($company ?? $this->company)->id,
            'title' => 'Article '.$sequence,
            'slug' => 'article-'.$sequence,
            'content' => '<p>Contenu de l’article '.$sequence.'</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    private function admin(): User
    {
        return $this->userWithRole('Admin');
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach($this->company->id);
        $user->assignRole($role);

        return $user;
    }
}
