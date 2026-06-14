<?php

namespace Database\Seeders;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\ClientFactory;
use Database\Factories\ExpenseFactory;
use Database\Factories\InvoiceFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        if (!config('demo.enabled')) {
            return;
        }

        $allowedEnvironments = config('demo.allowed_environments', ['local', 'development', 'staging', 'testing']);

        if (!app()->environment($allowedEnvironments)) {
            return;
        }

        $company = Company::updateOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Transport SARL',
                'slug' => 'demo',
                'email' => 'demo@erp.test',
                'website' => 'https://demo.erp.test',
                'city' => 'Bamako',
                'country' => 'Mali',
                'currency' => 'FCFA',
                'is_active' => true,
                'is_demo' => true,
                // The Company::booted() creating hook only fires on insert. On re-seed
                // (updateOrCreate hits an existing row) advanced_options would stay null,
                // disabling all features. Set them explicitly so re-seeding is idempotent.
                'advanced_options' => array_replace(
                    (array) config('erp.company_features.defaults', []),
                    [
                        'quotes' => true,
                        'credit_notes' => true,
                        'recurring_invoices' => true,
                        'general_ledger' => true,
                        'financial_periods' => true,
                        'documents' => true,
                        'advanced_reports' => true,
                    ]
                ),
            ],
        );

        $hadCurrentCompany = app()->bound('currentCompany');
        $previousCompany = $hadCurrentCompany ? app('currentCompany') : null;

        app()->instance('currentCompany', $company);

        try {
            $this->call(LedgerAccountsSeeder::class);

            $users = $this->seedUsers($company);
            $clients = $this->seedClients($company);
            $services = $this->seedServices($company);
            $this->seedProjects($company, $clients, $services, $users);
            $this->seedInvoicesAndPayments($company, $clients, $services, $users);
            $this->seedExpenses($company, $users);
            $this->seedActivity($company, $users);
        } finally {
            if ($hadCurrentCompany) {
                app()->instance('currentCompany', $previousCompany);
            } else {
                app()->forgetInstance('currentCompany');
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function seedUsers(Company $company): Collection
    {
        $password = (string) config('demo.password', 'DemoPass!123');

        $definitions = [
            [
                'name' => 'Demo Super Admin',
                'email' => 'admin@demo.erp',
                'department' => 'Management',
                'role' => 'Super Admin',
                'pivot_role' => 'owner',
            ],
            [
                'name' => 'Demo Accountant',
                'email' => 'accountant@demo.erp',
                'department' => 'Finance',
                'role' => 'Finance',
                'pivot_role' => 'finance',
            ],
            [
                'name' => 'Demo Sales',
                'email' => 'sales@demo.erp',
                'department' => 'Sales',
                'role' => 'Staff',
                'pivot_role' => 'staff',
            ],
            [
                'name' => 'Demo Manager',
                'email' => 'manager@demo.erp',
                'department' => 'Projects',
                'role' => 'Project Manager',
                'pivot_role' => 'manager',
            ],
            [
                'name' => 'Demo Assistant',
                'email' => 'assistant@demo.erp',
                'department' => 'Operations',
                'role' => 'Admin',
                'pivot_role' => 'admin',
            ],
        ];

        return collect($definitions)->map(function (array $definition) use ($company, $password): User {
            $user = User::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'department' => $definition['department'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make($password),
                ],
            );

            $user->syncRoles([$definition['role']]);
            $company->users()->syncWithoutDetaching([
                $user->id => ['role' => $definition['pivot_role']],
            ]);

            return $user;
        });
    }

    /**
     * @return Collection<int, Client>
     */
    private function seedClients(Company $company): Collection
    {
        $namedClients = [
            ['company_name' => 'Mali Telecom', 'contact_name' => 'Aminata Traoré'],
            ['company_name' => 'Bamako Distribution', 'contact_name' => 'Moussa Coulibaly'],
            ['company_name' => 'Africa Transit', 'contact_name' => 'Ibrahim Diallo'],
            ['company_name' => 'SOTRAMA Express', 'contact_name' => 'Mariama Koné'],
        ];

        $clients = collect($namedClients)->map(function (array $client) use ($company): Client {
            return Client::updateOrCreate(
                ['company_id' => $company->id, 'company_name' => $client['company_name']],
                [
                    'type' => 'company',
                    'contact_name' => $client['contact_name'],
                    'email' => strtolower(str_replace(' ', '', $client['company_name'])) . '@example.test',
                    'phone' => fake()->e164PhoneNumber(),
                    'city' => 'Bamako',
                    'country' => 'Mali',
                    'status' => 'active',
                ],
            );
        });

        $additionalClients = ClientFactory::new()
            ->count(20)
            ->state(['company_id' => $company->id, 'status' => 'active', 'country' => 'Mali'])
            ->create();

        return $clients->merge($additionalClients);
    }

    /**
     * @return Collection<int, Service>
     */
    private function seedServices(Company $company): Collection
    {
        $serviceDefinitions = [
            ['code' => 'SVC-2001', 'name' => 'Fleet Tracking Installation', 'category' => 'logistics', 'default_price' => 165000],
            ['code' => 'SVC-2002', 'name' => 'Monthly GPS Subscription', 'category' => 'logistics', 'default_price' => 22000],
            ['code' => 'SVC-2003', 'name' => 'ERP Deployment', 'category' => 'consulting', 'default_price' => 450000],
            ['code' => 'SVC-2004', 'name' => 'Web Hosting', 'category' => 'technology', 'default_price' => 90000],
        ];

        $services = collect($serviceDefinitions)->map(function (array $service) use ($company): Service {
            return Service::updateOrCreate(
                ['company_id' => $company->id, 'code' => $service['code']],
                [
                    'name' => $service['name'],
                    'category' => $service['category'],
                    'description' => $service['name'] . ' service package',
                    'default_price' => $service['default_price'],
                    'is_active' => true,
                ],
            );
        });

        $extraServices = ServiceFactory::new()->count(8)->state(['company_id' => $company->id])->create();

        return $services->merge($extraServices);
    }

    private function seedProjects(Company $company, Collection $clients, Collection $services, Collection $users): void
    {
        $projectNames = [
            'GPS rollout',
            'ERP deployment',
            'Server migration',
            'Regional routing optimization',
            'Fleet operations dashboard',
            'Warehouse connectivity upgrade',
        ];

        foreach ($projectNames as $name) {
            ProjectFactory::new()->state([
                'company_id' => $company->id,
                'name' => $name,
                'client_id' => $clients->random()->id,
                'service_id' => $services->random()->id,
                'assigned_to' => $users->random()->id,
                'created_by' => $users->first()?->id,
                'updated_by' => $users->first()?->id,
                'status' => fake()->randomElement(['planned', 'in_progress', 'on_hold', 'completed']),
                'approval_status' => fake()->randomElement(['pending', 'approved']),
            ])->create();
        }
    }

    private function seedInvoicesAndPayments(Company $company, Collection $clients, Collection $services, Collection $users): void
    {
        $profiles = [
            'draft',
            'sent',
            'sent',
            'overdue',
            'partial',
            'paid',
            'paid',
        ];

        for ($index = 0; $index < 120; $index++) {
            $profile = $profiles[array_rand($profiles)];
            $issueDate = fake()->dateTimeBetween('-8 months', '-5 days');
            $dueDate = (clone $issueDate)->modify('+15 days');

            if ($profile === 'overdue') {
                $dueDate = fake()->dateTimeBetween('-4 months', '-3 days');
            }

            if ($profile === 'sent') {
                $dueDate = fake()->dateTimeBetween('+1 day', '+45 days');
            }


            $invoiceNumber = InvoiceResource::generateInvoiceNumber(
                new Carbon($issueDate),
                $company->id,
            );

            $invoice = InvoiceFactory::new()->state([
                'invoice_number' => $invoiceNumber,
                'company_id' => $company->id,
                'client_id' => $clients->random()->id,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => in_array($profile, ['draft', 'sent'], true) ? $profile : 'sent',
                'created_by' => $users->first()?->id,
                'updated_by' => $users->first()?->id,
            ])->create();

            $itemCount = random_int(1, 3);

            for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
                $service = $services->random();

                InvoiceItem::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'service_id' => $service->id,
                    'description' => $service->name,
                    'quantity' => random_int(1, 4),
                    'unit_price' => $service->default_price,
                ]);
            }

            $invoice->refresh();

            if ($profile === 'draft') {
                $invoice->forceFill(['status' => 'draft'])->saveQuietly();

                continue;
            }

            if ($profile === 'paid') {
                $this->createInvoicePayments($invoice, $users, (float) $invoice->total);
                $invoice->refreshFinancials();

                continue;
            }

            if ($profile === 'partial') {
                $partialAmount = round((float) $invoice->total * fake()->randomFloat(2, 0.35, 0.75), 2);
                $this->createInvoicePayments($invoice, $users, $partialAmount);
                $invoice->refreshFinancials();

                continue;
            }

            $invoice->refreshFinancials();
        }
    }

    private function createInvoicePayments(Invoice $invoice, Collection $users, float $amount): void
    {
        $remaining = $amount;
        $paymentChunks = $amount > 0 ? random_int(1, 2) : 1;

        for ($chunk = 1; $chunk <= $paymentChunks; $chunk++) {
            $remainingChunks = ($paymentChunks - $chunk) + 1;

            $paymentAmount = $chunk === $paymentChunks
                ? $remaining
                : round($remaining / $remainingChunks, 2);

            $remaining = round($remaining - $paymentAmount, 2);

            if ($paymentAmount <= 0) {
                continue;
            }

            PaymentFactory::new()->state([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_date' => fake()->dateTimeBetween((string) $invoice->issue_date, 'now'),
                'amount' => $paymentAmount,
                'payment_method' => fake()->randomElement(['mobile_money', 'bank_transfer', 'cash']),
                'mobile_money_operator' => fake()->randomElement(['Orange Money', null]),
                'allow_overpayment' => true,
                'recorded_by' => $users->random()->id,
            ])->create();
        }
    }

    private function seedExpenses(Company $company, Collection $users): void
    {
        $titles = [
            'Fuel refill',
            'Monthly salaries',
            'Internet subscription',
            'Hosting renewal',
            'Transportation allowance',
        ];

        for ($index = 0; $index < 60; $index++) {
            ExpenseFactory::new()->state([
                'company_id' => $company->id,
                'title' => $titles[array_rand($titles)],
                'recorded_by' => $users->random()->id,
                'approval_status' => fake()->randomElement(['pending', 'approved', 'review']),
                'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
            ])->create();
        }
    }

    private function seedActivity(Company $company, Collection $users): void
    {
        $actions = [
            'invoice_created',
            'invoice_sent',
            'payment_recorded',
            'expense_created',
            'project_started',
        ];

        for ($index = 0; $index < 40; $index++) {
            ActivityLog::create([
                'company_id' => $company->id,
                'user_id' => $users->random()->id,
                'action' => $actions[array_rand($actions)],
                'meta_json' => [
                    'source' => 'demo_seed',
                    'sequence' => $index + 1,
                ],
            ]);
        }
    }
}
