<?php

namespace Tests\Feature;

use App\Models\User;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use Crommix\Blog\Models\BlogPost;
use Crommix\Blog\Support\RichContentMarkdown;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlogSourceEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $company = currentCompany();
        $company->update(['advanced_options' => ['blog' => true]]);

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->companies()->attach($company->id);
        $this->admin->assignRole('Admin');

        Filament::setCurrentPanel(Filament::getPanel('blog'));
    }

    public function test_html_source_edit_replaces_the_content(): void
    {
        $post = $this->makePost('<p>Ancien</p>');

        Livewire::actingAs($this->admin)
            ->test(EditBlogPost::class, ['record' => $post->getKey()])
            ->mountAction(TestAction::make('editHtmlSource')->schemaComponent('content'))
            ->assertActionDataSet(['source' => '<p>Ancien</p>'])
            ->setActionData(['source' => '<h2>Nouveau titre</h2><p>Texte <strong>gras</strong></p><script>alert(1)</script>'])
            ->callMountedAction()
            ->call('save')
            ->assertHasNoFormErrors();

        $content = $post->fresh()->content;
        $this->assertStringContainsString('<h2>Nouveau titre</h2>', $content);
        $this->assertStringContainsString('<strong>gras</strong>', $content);
        $this->assertStringNotContainsString('script', $content);
    }

    public function test_markdown_edit_is_prefilled_and_converted_to_html(): void
    {
        $post = $this->makePost('<h2>Titre</h2><p>Un <a href="https://crommixmali.com">lien</a> et <em>italique</em>.</p><ul><li><p>un</p></li><li><p>deux</p></li></ul>');

        Livewire::actingAs($this->admin)
            ->test(EditBlogPost::class, ['record' => $post->getKey()])
            ->mountAction(TestAction::make('editMarkdownSource')->schemaComponent('content'))
            ->assertActionDataSet(['source' => "## Titre\n\nUn [lien](https://crommixmali.com) et *italique*.\n\n- un\n- deux\n"])
            ->setActionData(['source' => "## Résultats\n\n| Langue | F1 |\n|---|---|\n| bm | 0.9 |\n\n```python\nprint('ok')\n```\n"])
            ->callMountedAction()
            ->call('save')
            ->assertHasNoFormErrors();

        $content = $post->fresh()->content;
        $this->assertStringContainsString('<h2>Résultats</h2>', $content);
        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('language-python', $content);
    }

    public function test_source_actions_work_on_a_new_post(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateBlogPost::class)
            ->fillForm(['title' => 'Depuis Markdown', 'slug' => 'depuis-markdown', 'status' => 'draft'])
            ->callAction(TestAction::make('editMarkdownSource')->schemaComponent('content'), data: [
                'source' => 'Bonjour **Bamako**',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('<strong>Bamako</strong>', BlogPost::query()->where('slug', 'depuis-markdown')->value('content'));
    }

    public function test_markdown_round_trip_keeps_structure(): void
    {
        $editor = RichContentRenderer::make()->getEditor();
        $html = '<h2>A</h2><p>Texte avec <code>code</code>, <s>barré</s>, <u>souligné</u> et 1. pas une liste</p>'
            .'<blockquote><p>Citation</p></blockquote><ol start="3"><li><p>trois</p></li></ol><hr><p style="text-align: center">Centré</p>';

        $document = $editor->setContent($html)->getDocument();
        $markdown = (new RichContentMarkdown($editor))->toMarkdown($document);

        $this->assertStringContainsString('## A', $markdown);
        $this->assertStringContainsString('`code`', $markdown);
        $this->assertStringContainsString('~~barré~~', $markdown);
        $this->assertStringContainsString('<u>souligné</u>', $markdown);
        $this->assertStringContainsString('> Citation', $markdown);
        $this->assertStringContainsString('3. trois', $markdown);
        $this->assertStringContainsString('text-align: center', $markdown);

        $back = (string) $editor->setContent(RichContentMarkdown::toHtml($markdown))->getHtml();
        foreach (['<h2>A</h2>', '<code>code</code>', '<s>barré</s>', '<u>souligné</u>', 'et 1. pas une liste</p>', '<blockquote>', 'start="3"', '<hr>', 'text-align: center'] as $fragment) {
            $this->assertStringContainsString($fragment, $back);
        }
    }

    public function test_tight_markdown_lists_become_editor_friendly_paragraphs(): void
    {
        $html = RichContentMarkdown::toHtml("- un\n  - sous-point\n- deux");

        $this->assertMatchesRegularExpression('#<li><p>un\s*</p><ul>\s*<li><p>sous-point</p></li>#', $html);
        $this->assertStringContainsString('<li><p>deux</p></li>', $html);
    }

    private function makePost(string $content): BlogPost
    {
        return BlogPost::create([
            'title' => 'Article',
            'slug' => 'article-'.uniqid(),
            'content' => $content,
            'status' => 'draft',
        ]);
    }
}
