<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\User;
use App\Services\LedgerPostingService;
use Crommix\Inventory\Models\Product;
use Crommix\POS\Services\PosService;
use Crommix\Procurement\Models\PurchaseOrder;
use Crommix\Procurement\Models\Supplier;
use Crommix\Procurement\Services\ProcurementService;
use Database\Seeders\LedgerAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Cross-module integrations:
 *  1. POS sale → stock deduction + balanced ledger entry.
 *  2. PO receiving → stock increase + received status.
 *  3. Payroll completion → balanced ledger entry (gross = net + deductions).
 */
class ModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Modules are disabled by default (config/crommix_modules.php reads
        // env at boot). Enable them BEFORE the app is created so the package
        // migrations are loaded for RefreshDatabase.
        putenv('CROMMIX_INVENTORY_ENABLED=true');
        putenv('CROMMIX_PROCUREMENT_ENABLED=true');
        putenv('CROMMIX_POS_ENABLED=true');

        parent::setUp();

        $this->seed(LedgerAccountsSeeder::class);
        Auth::setUser(User::factory()->create(['status' => 'active']));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('CROMMIX_INVENTORY_ENABLED');
        putenv('CROMMIX_PROCUREMENT_ENABLED');
        putenv('CROMMIX_POS_ENABLED');
    }

    public function test_pos_sale_deducts_stock_and_posts_balanced_ledger_entry(): void
    {
        $product = Product::create([
            'sku' => 'POS-SKU-1',
            'name' => 'Bottled Water',
            'unit' => 'pcs',
            'cost_price' => 200,
            'sale_price' => 500,
            'stock_quantity' => 10,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $posService = app(PosService::class);
        $session = $posService->openSession(5000);

        $order = $posService->processSale($session, [
            'payment_method' => 'cash',
            'amount_paid' => 1500,
        ], [
            [
                'product_id' => $product->id,
                'name' => 'Bottled Water',
                'quantity' => 3,
                'unit_price' => 500,
            ],
        ]);

        // Stock deducted.
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);

        // Totals computed.
        $this->assertSame(1500.0, (float) $order->total_amount);

        // Ledger entry posted and balanced.
        $entry = JournalEntry::query()
            ->where('source_type', 'pos_order')
            ->where('source_id', $order->id)
            ->first();

        $this->assertNotNull($entry, 'POS sale must post a journal entry.');
        $this->assertSame('posted', $entry->status);
        $this->assertSame(
            (float) $entry->lines->sum('debit'),
            (float) $entry->lines->sum('credit'),
            'Journal entry must be balanced.',
        );
        $this->assertSame(1500.0, (float) $entry->lines->sum('debit'));
    }

    public function test_receiving_a_purchase_order_increases_stock(): void
    {
        $product = Product::create([
            'sku' => 'PO-SKU-1',
            'name' => 'Printer Paper',
            'unit' => 'box',
            'cost_price' => 3000,
            'sale_price' => 4500,
            'stock_quantity' => 2,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Papeterie du Fleuve',
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-TEST-001',
            'status' => 'approved',
            'order_date' => now()->toDateString(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'description' => 'Printer Paper',
            'quantity' => 5,
            'unit_price' => 3000,
            'total_price' => 15000,
        ]);

        app(ProcurementService::class)->receive($order);

        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
        $this->assertSame(5.0, (float) $order->items()->first()->quantity_received);

        // Receiving twice must not double the stock (idempotent on outstanding qty).
        $order->fresh()->update(['status' => 'approved']);
        app(ProcurementService::class)->receive($order->fresh());
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    public function test_receiving_is_rejected_for_draft_orders(): void
    {
        $supplier = Supplier::create(['name' => 'Draft Supplier', 'is_active' => true]);

        $order = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
        ]);

        $this->expectException(\RuntimeException::class);
        app(ProcurementService::class)->receive($order);
    }

    public function test_payroll_posting_is_balanced_and_idempotent(): void
    {
        $ledger = app(LedgerPostingService::class);

        $entry = $ledger->postPayrollRun(
            date: now()->toDateString(),
            reference: 'PAY-2026-07',
            sourceId: 4242,
            gross: 1_000_000,
            deductions: 180_000,
            net: 820_000,
            userId: Auth::id(),
        );

        $this->assertNotNull($entry);
        $this->assertSame('posted', $entry->status);
        $this->assertSame(1_000_000.0, (float) $entry->lines->sum('debit'));
        $this->assertSame(
            (float) $entry->lines->sum('debit'),
            (float) $entry->lines->sum('credit'),
        );

        // Posting the same run again must return the same entry, not a duplicate.
        $second = $ledger->postPayrollRun(
            date: now()->toDateString(),
            reference: 'PAY-2026-07',
            sourceId: 4242,
            gross: 1_000_000,
            deductions: 180_000,
            net: 820_000,
            userId: Auth::id(),
        );

        $this->assertSame($entry->id, $second->id);
        $this->assertSame(1, JournalEntry::query()->where('source_type', 'payroll_run')->count());
    }

    public function test_unbalanced_payroll_posting_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LedgerPostingService::class)->postPayrollRun(
            date: now()->toDateString(),
            reference: 'PAY-BROKEN',
            sourceId: 999,
            gross: 1_000_000,
            deductions: 100_000,
            net: 820_000, // 80k missing
        );
    }
}
