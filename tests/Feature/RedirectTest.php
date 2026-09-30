<?php

namespace Tests\Feature;

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\Company;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // "Test Company" (bound by the base TestCase) is the first active
        // company, hence the one whose public site is served.
        $this->company = currentCompany();
    }

    public function test_short_link_redirects_and_counts_the_visit(): void
    {
        $redirect = $this->makeRedirect('/ai', 'https://ai.crommixmali.com');

        $this->get('/ai')->assertStatus(302)->assertRedirect('https://ai.crommixmali.com');
        $this->get('/AI/')->assertRedirect('https://ai.crommixmali.com');

        $redirect->refresh();
        $this->assertSame(2, $redirect->hits);
        $this->assertNotNull($redirect->last_hit_at);
    }

    public function test_permanent_redirect_uses_301(): void
    {
        $this->makeRedirect('/lid', 'https://ai.crommixmali.com/lid', ['status_code' => 301]);

        $this->get('/lid')->assertStatus(301)->assertRedirect('https://ai.crommixmali.com/lid');
    }

    public function test_query_string_is_preserved_unless_disabled(): void
    {
        $this->makeRedirect('/ai', 'https://ai.crommixmali.com/?src=site');
        $this->makeRedirect('/nu', 'https://ai.crommixmali.com', ['preserve_query' => false]);

        $this->get('/ai?ref=linkedin')->assertRedirect('https://ai.crommixmali.com/?src=site&ref=linkedin');
        $this->get('/nu?ref=linkedin')->assertRedirect('https://ai.crommixmali.com');
    }

    public function test_sub_paths_are_forwarded_only_when_enabled(): void
    {
        $this->makeRedirect('/ai', 'https://ai.crommixmali.com/', ['include_subpaths' => true]);
        $this->makeRedirect('/docs', 'https://docs.example.com');

        $this->get('/ai/models/lid?v=2')->assertRedirect('https://ai.crommixmali.com/models/lid?v=2');
        $this->get('/docs/intro')->assertNotFound();
    }

    public function test_longest_sub_path_prefix_wins(): void
    {
        $this->makeRedirect('/ai', 'https://ai.crommixmali.com', ['include_subpaths' => true]);
        $this->makeRedirect('/ai/lid', 'https://lid.crommixmali.com', ['include_subpaths' => true]);

        $this->get('/ai/lid/demo')->assertRedirect('https://lid.crommixmali.com/demo');
        $this->get('/ai/tts')->assertRedirect('https://ai.crommixmali.com/tts');
    }

    public function test_inactive_unknown_and_foreign_redirects_return_404(): void
    {
        $this->makeRedirect('/off', 'https://example.com', ['is_active' => false]);

        $other = Company::create(['name' => 'Autre', 'currency' => 'FCFA', 'is_active' => true]);
        $this->makeRedirect('/theirs', 'https://example.com', [], $other);

        $this->get('/off')->assertNotFound();
        $this->get('/nothing-here')->assertNotFound();
        $this->get('/theirs')->assertNotFound();
    }

    public function test_redirect_never_shadows_an_existing_page(): void
    {
        $this->makeRedirect('/about', 'https://example.com');

        $this->get('/about')->assertOk();
    }

    public function test_changes_take_effect_immediately(): void
    {
        $redirect = $this->makeRedirect('/ai', 'https://old.example.com');
        $this->get('/ai')->assertRedirect('https://old.example.com');

        $redirect->update(['target_url' => 'https://ai.crommixmali.com']);
        $this->get('/ai')->assertRedirect('https://ai.crommixmali.com');

        $redirect->delete();
        $this->get('/ai')->assertNotFound();
    }

    // ── Admin ───────────────────────────────────────────────────────────────

    public function test_admin_creates_a_redirect_from_the_admin_screen(): void
    {
        $admin = $this->userWithRole('Admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(ManageRedirects::class)
            ->callAction('create', data: [
                'source_path' => 'AI/',
                'target_url' => 'https://ai.crommixmali.com',
                'status_code' => 302,
                'include_subpaths' => false,
                'preserve_query' => true,
                'is_active' => true,
            ])
            ->assertHasNoFormErrors();

        $redirect = Redirect::query()->sole();
        $this->assertSame('/ai', $redirect->source_path);
        $this->assertSame($this->company->id, $redirect->company_id);

        $this->get('/ai')->assertRedirect('https://ai.crommixmali.com');
    }

    public function test_form_rejects_taken_paths_duplicates_and_loops(): void
    {
        $admin = $this->userWithRole('Admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->makeRedirect('/ai', 'https://ai.crommixmali.com');

        $create = fn (array $data) => Livewire::actingAs($admin)
            ->test(ManageRedirects::class)
            ->callAction('create', data: $data + ['status_code' => 302]);

        $create(['source_path' => '/about', 'target_url' => 'https://example.com'])
            ->assertHasFormErrors(['source_path']);

        $create(['source_path' => '/ai', 'target_url' => 'https://example.com'])
            ->assertHasFormErrors(['source_path' => 'unique']);

        $create(['source_path' => '/', 'target_url' => 'https://example.com'])
            ->assertHasFormErrors(['source_path']);

        $create(['source_path' => '/boucle', 'target_url' => url('/boucle')])
            ->assertHasFormErrors(['target_url']);

        // Same site, different page: allowed.
        $create(['source_path' => '/equipe', 'target_url' => url('/about')])
            ->assertHasNoFormErrors();
    }

    public function test_only_permitted_roles_manage_redirects(): void
    {
        $this->actingAs($this->userWithRole('Admin'));
        $this->assertTrue(RedirectResource::canCreate());
        $this->get('/admin/redirects')->assertOk();

        $this->actingAs($this->userWithRole('Read Only'));
        $this->assertTrue(RedirectResource::canViewAny());
        $this->assertFalse(RedirectResource::canCreate());

        $this->actingAs($this->userWithRole('Finance'));
        $this->assertFalse(RedirectResource::canViewAny());
        $this->get('/admin/redirects')->assertForbidden();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeRedirect(string $source, string $target, array $attributes = [], ?Company $company = null): Redirect
    {
        return Redirect::withoutCompanyScope()->create(array_merge([
            'company_id' => ($company ?? $this->company)->id,
            'source_path' => $source,
            'target_url' => $target,
            'status_code' => 302,
        ], $attributes));
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
