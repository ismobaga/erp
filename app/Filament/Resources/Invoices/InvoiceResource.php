<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Concerns\HasBillingFormConcerns;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    use HasPermissionAccess;
    use HasBillingFormConcerns;

    protected static string $permissionScope = 'invoices';

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité de la facture')
                            ->description('Créez un document de facturation officiel lié au suivi comptable opérationnel.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Numéro de facture')
                                    ->placeholder('Attribué automatiquement à l’enregistrement')
                                    ->helperText('Attribué automatiquement selon la séquence fiscale active et figé après émission.')
                                    ->default(fn(): string => static::generateInvoiceNumber())
                                    ->readOnly()
                                    ->dehydrated(false),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload()
                                    ->required(),
                                Select::make('quote_id')
                                    ->label('Devis lié')
                                    ->relationship('quote', 'quote_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $quote = Quote::find($state);

                                        if (!$quote) {
                                            return;
                                        }

                                        $set('client_id', $quote->client_id);
                                        $set('discount_total', $quote->discount_total);
                                        $set('tax_total', $quote->tax_total);
                                        $set('notes', $quote->notes);
                                    }),
                                DatePicker::make('issue_date')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $set('invoice_number', static::generateInvoiceNumber($state));
                                    }),
                                DatePicker::make('due_date')
                                    ->default(now()->addDays((int) config('erp.billing.invoice_default_due_days', 30))),
                            ]),
                        Section::make('État comptable')
                            ->description('Suivez le recouvrement et le niveau d’urgence du paiement.')
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
                        Section::make('Lignes de facture')
                            ->description('Définissez les prestations facturables, quantités et prix unitaires.')
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

                                                if (!$service) {
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
                                            ->content(fn(Get $get): string => static::formatMoney(((float) ($get('quantity') ?? 0)) * ((float) ($get('unit_price') ?? 0)))),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Notes de facturation')
                            ->description('Ajoutez les instructions, remarques d’envoi et consignes de recouvrement.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Notes administratives')
                                    ->rows(6)
                                    ->placeholder('Ajoutez les instructions de paiement, le contexte interne ou les références contractuelles...'),
                            ]),
                        Section::make('Résumé financier')
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
                                    ->label('Taxes totales')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                Placeholder::make('subtotal_preview')
                                    ->label('Sous-total')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['subtotal'])),
                                Placeholder::make('grand_total_preview')
                                    ->label('Total à recevoir')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['total'])),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issue_date', 'desc')
            ->recordUrl(fn(Invoice $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('erp.common.invoice'))
                    ->description(fn(Invoice $record): string => $record->quote?->quote_number
                        ? __('erp.resources.invoice.linked_quote', ['quote' => $record->quote->quote_number])
                        : __('erp.resources.invoice.standalone_billing'))
                    ->searchable(),
                TextColumn::make('client_name')
                    ->label(__('erp.common.client'))
                    ->state(fn(Invoice $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: __('erp.common.account_client'))
                    ->description(fn(Invoice $record): string => $record->client?->email ?: __('erp.common.no_billing_email')),
                TextColumn::make('issue_date')
                    ->label(__('erp.resources.invoice.issue_and_due'))
                    ->state(fn(Invoice $record): string => optional($record->issue_date)->format('M d, Y') ?? __('erp.common.not_issued'))
                    ->description(fn(Invoice $record): string => $record->due_date
                        ? __('erp.resources.invoice.due_prefix', ['date' => optional($record->due_date)->format('M d, Y') ?? __('erp.common.to_define')])
                        : __('erp.resources.invoice.no_due_date'))
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('erp.resources.invoice.total_amount'))
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('period_lock_status')
                    ->label('Période')
                    ->state(fn(Invoice $record): string => static::lockStatusLabel($record))
                    ->badge()
                    ->color(fn(Invoice $record): string => static::lockStatusColor($record)),
                TextColumn::make('balance_due')
                    ->label(__('erp.resources.invoice.balance_due'))
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->description(fn(Invoice $record): string => (float) $record->paid_total > 0
                        ? __('erp.resources.invoice.paid_prefix', ['amount' => static::formatMoney((float) $record->paid_total)])
                        : __('erp.resources.invoice.no_payment_recorded'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('erp.common.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => __('erp.resources.invoice.statuses.' . $state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('erp.common.status'))
                    ->options(trans('erp.resources.invoice.statuses')),
            ])
            ->recordActions([
                Action::make('sendReminder')
                    ->label(__('erp.actions.send_reminder'))
                    ->visible(fn(Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && (auth()->user()?->can('invoices.update') ?? false))
                    ->action(function (Invoice $record): void {
                        $client = $record->client;
                        if (!$client || blank($client->email)) {
                            Notification::make()
                                ->title('Aucun e-mail client')
                                ->body('Ce client n\'a pas d\'adresse e-mail enregistrée. Vérifiez sa fiche.')
                                ->warning()
                                ->send();
                            return;
                        }
                        \Mail::to($client->email)->queue(new \App\Mail\InvoiceReminderMail($record));
                        app(\App\Services\AuditTrailService::class)->log('invoice_reminder_sent', $record, [
                            'reference' => $record->invoice_number,
                            'client_email' => $client->email,
                            'balance_due' => (float) $record->balance_due,
                            'due_date' => optional($record->due_date)->format('Y-m-d'),
                            'sent_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Rappel de paiement envoyé')
                            ->body('Un rappel a été envoyé à ' . $client->email . ' pour la facture ' . $record->invoice_number . '.')
                            ->success()
                            ->send();
                    }),
                Action::make('exportPdf')
                    ->label(__('erp.actions.export_pdf'))
                    ->visible(fn(): bool => auth()->user()?->canAny(['invoices.view', 'reports.view']) ?? false)
                    ->url(fn(Invoice $record): string => route('invoices.pdf', ['invoice' => $record, 'download' => 1])),
                Action::make('sendWhatsapp')
                    ->label('Envoyer via WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn(Invoice $record): bool => filled($record->client?->phone) && (auth()->user()?->can('invoices.view') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la facture via WhatsApp')
                    ->modalDescription('La facture sera envoyée en PDF via WhatsApp au numéro du client.')
                    ->action(function (Invoice $record, \App\Services\Whatsapp\WhatsappSendService $service): void {
                        $log = $service->sendInvoice($record);
                        if ($log->status === 'sent') {
                            Notification::make()->title('Facture envoyée via WhatsApp')->success()->send();
                        } else {
                            Notification::make()->title('Échec de l\'envoi WhatsApp')->body($log->error_message)->danger()->send();
                        }
                    }),
                Action::make('sendWhatsappReminder')
                    ->label('Rappel WhatsApp')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->visible(fn(Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && filled($record->client?->phone) && (auth()->user()?->can('invoices.view') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer un rappel de paiement via WhatsApp')
                    ->action(function (Invoice $record, \App\Services\Whatsapp\WhatsappSendService $service): void {
                        $log = $service->sendPaymentReminder($record);
                        if ($log->status === 'sent') {
                            Notification::make()->title('Rappel envoyé via WhatsApp')->success()->send();
                        } else {
                            Notification::make()->title('Échec de l\'envoi WhatsApp')->body($log->error_message)->danger()->send();
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
            ->emptyStateDescription('Créez votre première facture pour commencer à suivre vos créances.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['client', 'quote']);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccessPermission('update')
            && (!static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessPermission('delete')
            && (!static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    protected static function isRecordLocked(Model $record): bool
    {
        return $record instanceof Invoice && FinancialPeriod::isDateLocked($record->issue_date);
    }

    protected static function lockStatusLabel(Model $record): string
    {
        if (!static::isRecordLocked($record)) {
            return 'Ouverte';
        }

        return FinancialPeriod::currentUserCanOverrideLock() ? 'Dérogation' : 'Clôturée';
    }

    protected static function lockStatusColor(Model $record): string
    {
        if (!static::isRecordLocked($record)) {
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
     * @param mixed $issueDate Invoice issue date
     * @param int|null $companyId Company to scope the sequence to
     */
    public static function generateInvoiceNumber(mixed $issueDate = null, ?int $companyId = null): string
    {
        // If company_id not provided, try to resolve from current context
        if (!$companyId) {
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
