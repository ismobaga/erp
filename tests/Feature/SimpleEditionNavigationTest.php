<?php

namespace Tests\Feature;

use App\Filament\Pages\Analytics;
use App\Filament\Pages\ApprovalCenter;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\FinancialInsights;
use App\Filament\Pages\NotificationHub;
use App\Filament\Pages\OperationalResilience;
use App\Filament\Pages\ReportGeneration;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\CompanySettings\CompanySettingResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Ledger\LedgerAccountResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\ArchitecturalStatsOverview;
use App\Filament\Widgets\OnboardingChecklistWidget;
use App\Models\User;
use App\Support\ErpEdition;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleEditionNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config([
            'erp.edition.active' => 'simple',
            'erp.edition.profiles.simple.enabled_modules' => [
                'dashboard',
                'clients',
                'projects',
                'invoices',
                'payments',
                'expenses',
                'reports',
                'settings',
            ],
        ]);
    }

    public function test_simple_edition_exposes_the_core_sme_modules(): void
    {
        $this->assertTrue(ErpEdition::isSimple());
        $this->assertSame([
            'dashboard',
            'clients',
            'projects',
            'invoices',
            'payments',
            'expenses',
            'reports',
            'settings',
        ], ErpEdition::enabledModules());
    }

    public function test_growing_profile_unlocks_progressive_modules(): void
    {
        config([
            'erp.edition.active' => 'growing',
            'erp.edition.profiles.growing.enabled_modules' => [
                'dashboard',
                'clients',
                'projects',
                'quotes',
                'invoices',
                'payments',
                'expenses',
                'reports',
                'settings',
            ],
        ]);

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
        ], ErpEdition::enabledModules());
    }

    public function test_simple_edition_navigation_hides_advanced_modules_by_default(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $this->assertTrue(ClientResource::shouldRegisterNavigation());
        $this->assertTrue(ProjectResource::shouldRegisterNavigation());
        $this->assertTrue(InvoiceResource::shouldRegisterNavigation());
        $this->assertTrue(PaymentResource::shouldRegisterNavigation());
        $this->assertTrue(ExpenseResource::shouldRegisterNavigation());
        $this->assertTrue(Analytics::shouldRegisterNavigation());
        $this->assertTrue(CompanySettingResource::shouldRegisterNavigation());

        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(LedgerAccountResource::shouldRegisterNavigation());
        $this->assertFalse(FinancialPeriodResource::shouldRegisterNavigation());
        $this->assertFalse(ApprovalCenter::shouldRegisterNavigation());
        $this->assertFalse(NotificationHub::shouldRegisterNavigation());
        $this->assertFalse(FinancialInsights::shouldRegisterNavigation());
        $this->assertFalse(OperationalResilience::shouldRegisterNavigation());
        $this->assertFalse(ReportGeneration::shouldRegisterNavigation());
    }

    public function test_simple_edition_dashboard_keeps_only_business_first_widgets(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $dashboard = app(Dashboard::class);

        $this->assertSame([
            OnboardingChecklistWidget::class,
            ArchitecturalStatsOverview::class,
        ], $dashboard->getWidgets());
    }

    public function test_simple_edition_dashboard_surfaces_sme_metrics(): void
    {
        $widget = new class extends ArchitecturalStatsOverview
        {
            public function statsForTest(): array
            {
                return $this->getStats();
            }
        };

        $labels = collect($widget->statsForTest())
            ->map(fn ($stat): string => $stat->getLabel())
            ->all();

        $this->assertSame([
            __('erp.dashboard.money_in'),
            __('erp.dashboard.money_out'),
            __('erp.dashboard.outstanding_payments'),
            __('erp.dashboard.profitability'),
        ], $labels);
    }
}
