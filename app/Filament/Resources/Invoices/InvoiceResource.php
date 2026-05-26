<?php

namespace App\Filament\Resources\Invoices;

use App\Actions\ApplyPaymentAction;
use App\Actions\SendInvoiceReminderAction;
use App\Actions\SendInvoiceWhatsappAction;
use App\Actions\SendInvoiceWhatsappReminderAction;
use App\Filament\Concerns\HasBillingFormConcerns;
use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Payments\PaymentResource as PaymentsPaymentResource;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Service;
use App\Services\InvoiceNumberService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class InvoiceResource extends Resource
{
    use HasBillingFormConcerns;
    use HasPermissionAccess;

    protected static string $permissionScope = 'invoices';

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Facture')
                            ->description('Créez rapidement une facture client prête à être envoyée.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Numéro de facture')
                                    ->placeholder('Attribué automatiquement à l’enregistrement')
                                    ->helperText('Le numéro sera attribué automatiquement lors de l’enregistrement.')
                                    ->readOnly()
                                    ->dehydrated(false),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #'.$record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email', 'phone'])
                                    ->helperText('Recherchez par nom, contact, e-mail ou téléphone.')
                                    ->required(),
                                Select::make('recent_client_id')
                                    ->label('Clients récents')
                                    ->options(fn (): array => Client::query()
                                        ->orderByDesc('updated_at')
                                        ->limit(6)
                                        ->get()
                                        ->mapWithKeys(fn (Client $record): array => [$record->getKey() => ($record->company_name ?: $record->contact_name ?: ('Client #'.$record->getKey()))])
                                        ->all())
                                    ->searchable()
                                    ->native(false)
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set): mixed => $set('client_id', $state)),
                                Select::make('quote_id')
                                    ->label('Depuis un devis')
                                    ->relationship('quote', 'quote_number')
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $quote = Quote::findOrFail($state);

                                        $set('client_id', $quote->client_id);
                                        $set('discount_total', $quote->discount_total);
                                        $set('tax_total', $quote->tax_total);
                                        $set('notes', $quote->notes);
                                    }),
                                DatePicker::make('issue_date')
                                    ->label('Date d’émission')
                                    ->default(now())
                                    ->required()
                                    ->live(),
                                DatePicker::make('due_date')
                                    ->label('Date d’échéance (optionnelle)')
                                    ->default(now()->addDays((int) config('erp.billing.invoice_default_due_days', 30))),
                            ]),
                        Section::make('Suivi du paiement')
                            ->description('Gardez un œil sur le statut et le montant restant à recevoir.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('status')
                                    ->label(__('erp.common.status'))
                                    ->options(trans('erp.resources.invoice.statuses'))
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('collection_brief')
                                    ->label(__('erp.resources.invoice.collection_summary'))
                                    ->content(__('erp.resources.invoice.collection_brief')),
                            ]),
                        Section::make('Articles et services')
                            ->description('Ajoutez ce que vous facturez avec la quantité et le prix.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->defaultItems(1)
                                    ->addActionLabel('Ajouter une ligne')
                                    ->schema([
                                        Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if (blank($state)) {
                                                    return;
                                                }

                                                $service = Service::find($state);

                                                if (! $service) {
                                                    return;
                                                }

                                                $set('description', $service->name);
                                                $set('unit_price', $service->default_price);
                                            }),
                                        TextInput::make('description')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->required()
                                            ->live(),
                                        TextInput::make('unit_price')
                                            ->numeric()
                                            ->prefix('FCFA')
                                            ->default(0)
                                            ->minValue(0)
                                            ->required()
                                            ->live(),
                                        Placeholder::make('line_total_preview')
                                            ->label('Total de la ligne')
                                            ->content(fn (Get $get): string => static::formatMoney(((float) ($get('quantity') ?? 0)) * ((float) ($get('unit_price') ?? 0)))),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Notes et conditions')
                            ->description('Ajoutez des détails utiles pour le client si nécessaire.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Notes (optionnelles)')
                                    ->rows(6)
                                    ->placeholder('Ajoutez les instructions de paiement, le contexte interne ou les références contractuelles...'),
                            ]),
                        Section::make('Résumé')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 5])
                            ->schema([
                                TextInput::make('discount_total')
                                    ->label('Remise totale')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                TextInput::make('tax_total')
                                    ->label('Taxes (optionnelles)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                Placeholder::make('subtotal_preview')
                                    ->label('Sous-total')
                                    ->content(fn (Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['subtotal'])),
                                Placeholder::make('grand_total_preview')
                                    ->label('Total à recevoir')
                                    ->content(fn (Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['total'])),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issue_date', 'desc')
            ->recordUrl(fn (Invoice $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('erp.common.invoice'))
                    ->description(fn (Invoice $record): string => $record->quote?->quote_number
                        ? __('erp.resources.invoice.linked_quote', ['quote' => $record->quote->quote_number])
                        : __('erp.resources.invoice.standalone_billing'))
                    ->searchable(),
                TextColumn::make('client_name')
                    ->label(__('erp.common.client'))
                    ->state(fn (Invoice $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: __('erp.common.account_client'))
                    ->description(fn (Invoice $record): string => $record->client?->email ?: __('erp.common.no_billing_email')),
                TextColumn::make('issue_date')
                    ->label(__('erp.resources.invoice.issue_and_due'))
                    ->state(fn (Invoice $record): string => optional($record->issue_date)->format('M d, Y') ?? __('erp.common.not_issued'))
                    ->description(fn (Invoice $record): string => $record->due_date
                        ? __('erp.resources.invoice.due_prefix', ['date' => optional($record->due_date)->format('M d, Y') ?? __('erp.common.to_define')])
                        : __('erp.resources.invoice.no_due_date'))
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('erp.resources.invoice.total_amount'))
                    ->formatStateUsing(fn ($state): string => static::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('period_lock_status')
                    ->label('Période')
                    ->state(fn (Invoice $record): string => static::lockStatusLabel($record))
                    ->badge()
                    ->color(fn (Invoice $record): string => static::lockStatusColor($record)),
                TextColumn::make('balance_due')
                    ->label(__('erp.resources.invoice.balance_due'))
                    ->formatStateUsing(fn ($state): string => static::formatMoney((float) $state))
                    ->description(fn (Invoice $record): string => (float) $record->paid_total > 0
                        ? __('erp.resources.invoice.paid_prefix', ['amount' => static::formatMoney((float) $record->paid_total)])
                        : __('erp.resources.invoice.no_payment_recorded'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('erp.common.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => __('erp.resources.invoice.statuses.'.$state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('erp.common.status'))
                    ->options(trans('erp.resources.invoice.statuses')),
            ])
            ->recordActions([
                Action::make('sendReminder')
                    ->label(__('erp.actions.send_reminder'))
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && (auth()->user()?->can('invoices.update') ?? false))
                    ->action(fn (Invoice $record, SendInvoiceReminderAction $action) => $action->execute($record)),
                Action::make('exportPdf')
                    ->label(__('erp.actions.export_pdf'))
                    ->visible(fn (): bool => auth()->user()?->canAny(['invoices.view', 'reports.view']) ?? false)
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record, 'download' => 1])),
                Action::make('previewPdf')
                    ->label('Prévisualiser PDF')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (): bool => auth()->user()?->can('invoices.view') ?? false)
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record]))
                    ->openUrlInNewTab(),
                Action::make('sendWhatsapp')
                    ->label('Envoyer via WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => filled($record->client?->phone) && (auth()->user()?->can('invoices.view') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la facture via WhatsApp')
                    ->modalDescription('La facture sera envoyée en PDF via WhatsApp au numéro du client.')
                    ->action(fn (Invoice $record, SendInvoiceWhatsappAction $action) => $action->execute($record)),
                Action::make('sendWhatsappReminder')
                    ->label('Rappel WhatsApp')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && filled($record->client?->phone) && (auth()->user()?->can('invoices.view') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer un rappel de paiement via WhatsApp')
                    ->action(fn (Invoice $record, SendInvoiceWhatsappReminderAction $action) => $action->execute($record)),
                Action::make('recordPayment')
                    ->label('Encaisser (1 clic)')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => (float) $record->balance_due > 0 && in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && (auth()->user()?->can('payments.create') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Enregistrer le paiement complet')
                    ->modalDescription('Un paiement en espèces sera créé pour solder cette facture.')
                    ->action(function (Invoice $record, ApplyPaymentAction $action): void {
                        try {
                            $payment = Payment::make([
                                'invoice_id' => $record->getKey(),
                                'client_id' => $record->client_id,
                                'payment_date' => now()->toDateString(),
                                'payment_method' => 'cash',
                                'reference' => PaymentsPaymentResource::generatePaymentReference(),
                                'amount' => (float) $record->balance_due,
                                'recorded_by' => auth()->id(),
                            ]);

                            $action->execute($payment);

                            Notification::make()
                                ->title('Paiement enregistré')
                                ->body('La facture '.$record->invoice_number.' a été mise à jour automatiquement.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Paiement non enregistré')
                                ->body('Vérifiez les informations du paiement puis réessayez.')
                                ->danger()
                                ->send();
                        }
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucune facture pour le moment')
            ->emptyStateDescription('Créez votre première facture pour commencer à suivre les factures impayées.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'quote']);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccessPermission('update')
            && (! static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessPermission('delete')
            && (! static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    protected static function isRecordLocked(Model $record): bool
    {
        $date = $record instanceof Invoice
            ? $record->issue_date?->toDateString()
            : null;

        if ($date === null) {
            return false;
        }

        foreach (static::closedPeriodRanges() as [$start, $end]) {
            if ($date >= $start && $date <= $end) {
                return true;
            }
        }

        return false;
    }

    private static function closedPeriodRanges(): array
    {
        static $cache = [];

        $requestKey = app()->bound('request')
            ? (string) spl_object_id(app('request'))
            : 'no-request';
        $companyKey = app()->bound('currentCompany')
            ? (string) app('currentCompany')->id
            : 'no-company';
        $cacheKey = "{$requestKey}:{$companyKey}";

        return $cache[$cacheKey] ??= FinancialPeriod::query()
            ->closed()
            ->select('starts_on', 'ends_on')
            ->get()
            ->map(fn (FinancialPeriod $period): array => [
                $period->starts_on?->toDateString(),
                $period->ends_on?->toDateString(),
            ])
            ->filter(fn (array $range): bool => filled($range[0]) && filled($range[1]))
            ->values()
            ->all();
    }

    protected static function lockStatusLabel(Model $record): string
    {
        if (! static::isRecordLocked($record)) {
            return 'Ouverte';
        }

        return FinancialPeriod::currentUserCanOverrideLock() ? 'Dérogation' : 'Clôturée';
    }

    protected static function lockStatusColor(Model $record): string
    {
        if (! static::isRecordLocked($record)) {
            return 'success';
        }

        return FinancialPeriod::currentUserCanOverrideLock() ? 'warning' : 'danger';
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    /**
     * Generate an invoice number for Filament forms.
     *
     * @param  mixed  $issueDate  Invoice issue date
     * @param  int|null  $companyId  Company to scope the sequence to
     */
    public static function generateInvoiceNumber(mixed $issueDate = null, ?int $companyId = null): string
    {
        // If company_id not provided, try to resolve from current context
        if (! $companyId) {
            if (app()->bound('currentCompany')) {
                $companyId = app('currentCompany')->id;
            } elseif (auth()->check()) {
                // Fallback to user's first company (multi-tenant pattern)
                $companyId = auth()->user()->companies()->first()?->id;
            }
        }

        // Use explicit company_id to avoid currentCompany binding dependency
        return app(InvoiceNumberService::class)->generateForCompany(
            $issueDate ?? now(),
            $companyId,
        );
    }
}
