<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class LedgerAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ── Assets ──────────────────────────────────────────────────────
            ['code' => '1000', 'name' => 'Assets',                 'type' => 'asset',     'normal_balance' => 'debit',  'category' => 'root',           'parent_code' => null],
            ['code' => '1010', 'name' => 'Cash & Bank',            'type' => 'asset',     'normal_balance' => 'debit',  'category' => 'current_asset',  'parent_code' => '1000'],
            ['code' => '1100', 'name' => 'Accounts Receivable',    'type' => 'asset',     'normal_balance' => 'debit',  'category' => 'current_asset',  'parent_code' => '1000'],
            ['code' => '1200', 'name' => 'Prepaid Expenses',       'type' => 'asset',     'normal_balance' => 'debit',  'category' => 'current_asset',  'parent_code' => '1000'],
            ['code' => '1500', 'name' => 'Fixed Assets',           'type' => 'asset',     'normal_balance' => 'debit',  'category' => 'fixed_asset',    'parent_code' => '1000'],

            // ── Liabilities ─────────────────────────────────────────────────
            ['code' => '2000', 'name' => 'Liabilities',            'type' => 'liability', 'normal_balance' => 'credit', 'category' => 'root',           'parent_code' => null],
            ['code' => '2100', 'name' => 'Accounts Payable',       'type' => 'liability', 'normal_balance' => 'credit', 'category' => 'current_liability', 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Tax Payable',            'type' => 'liability', 'normal_balance' => 'credit', 'category' => 'current_liability', 'parent_code' => '2000'],
            ['code' => '2300', 'name' => 'Accrued Liabilities',    'type' => 'liability', 'normal_balance' => 'credit', 'category' => 'current_liability', 'parent_code' => '2000'],

            // ── Equity ──────────────────────────────────────────────────────
            ['code' => '3000', 'name' => 'Equity',                 'type' => 'equity',    'normal_balance' => 'credit', 'category' => 'root',           'parent_code' => null],
            ['code' => '3100', 'name' => 'Share Capital',          'type' => 'equity',    'normal_balance' => 'credit', 'category' => 'equity',         'parent_code' => '3000'],
            ['code' => '3200', 'name' => 'Retained Earnings',      'type' => 'equity',    'normal_balance' => 'credit', 'category' => 'equity',         'parent_code' => '3000'],

            // ── Revenue ─────────────────────────────────────────────────────
            ['code' => '4000', 'name' => 'Revenue',                'type' => 'revenue',   'normal_balance' => 'credit', 'category' => 'root',           'parent_code' => null],
            ['code' => '4100', 'name' => 'Sales Revenue',          'type' => 'revenue',   'normal_balance' => 'credit', 'category' => 'operating_revenue', 'parent_code' => '4000'],
            ['code' => '4200', 'name' => 'Other Income',           'type' => 'revenue',   'normal_balance' => 'credit', 'category' => 'other_revenue',  'parent_code' => '4000'],

            // ── Expenses ────────────────────────────────────────────────────
            ['code' => '5000', 'name' => 'Expenses',               'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'root',           'parent_code' => null],
            ['code' => '5100', 'name' => 'Travel Expenses',        'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'Office Supplies',        'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
            ['code' => '5300', 'name' => 'Operations',             'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
            ['code' => '5400', 'name' => 'Payroll Expenses',       'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
            ['code' => '5500', 'name' => 'Compliance Expenses',    'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
            ['code' => '5900', 'name' => 'Other Expenses',         'type' => 'expense',   'normal_balance' => 'debit',  'category' => 'operating_expense', 'parent_code' => '5000'],
        ];

        $companyId = app('currentCompany')->id;

        // First pass: upsert all accounts without parent linkage
        foreach ($accounts as $data) {
            LedgerAccount::updateOrCreate(
                ['company_id' => $companyId, 'code' => $data['code']],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'normal_balance' => $data['normal_balance'],
                    'category' => $data['category'],
                    'is_active' => true,
                    'parent_id' => null,
                ],
            );
        }

        // Second pass: wire up parent relationships
        foreach ($accounts as $data) {
            if (blank($data['parent_code'])) {
                continue;
            }

            $parent = LedgerAccount::where('code', $data['parent_code'])->first();

            if ($parent) {
                LedgerAccount::where('code', $data['code'])
                    ->update(['parent_id' => $parent->id]);
            }
        }
    }
}
