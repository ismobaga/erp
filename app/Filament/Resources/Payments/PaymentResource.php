<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Client;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'payments';

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité de la transaction')
                            ->description('Enregistrez les encaissements et rattachez-les aux factures en attente.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                DatePicker::make('payment_date')
                                    ->label('Date du paiement')
                                    ->default(now())
                                    ->required(),
                                Select::make('invoice_id')
                                    ->label('Facture liée')
                                    ->relationship('invoice', 'invoice_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->helperText('Optionnel à la saisie. Utilisez le rapprochement intelligent si le paiement est saisi avant son affectation.')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $invoice = Invoice::find($state);

                                        if (!$invoice) {
                                            return;
                                        }

                                        $set('client_id', $invoice->client_id);
                                    }),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload()
                                    ->required(),
                                Select::make('payment_method')
                                    ->label('Mode')
                                    ->options(trans('erp.payment_methods'))
                                    ->default('bank_transfer')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('reference')
                                    ->label('Référence')
                                    ->placeholder('BT-9902-XQ')
                                    ->columnSpanFull(),
                                TextInput::make('amount')
                                    ->label('Montant reçu')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->minValue(0.01)
                                    ->required(),
                            ]),
                        Section::make('Contrôles de règlement')
                            ->description('Appliquez les validations et les exceptions de rapprochement.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Toggle::make('allow_overpayment')
                                    ->label('Autoriser le trop-perçu')
                                    ->helperText('À activer uniquement après validation explicite de l’équipe finance.'),
                                Placeholder::make('tracking_notice')
                                    ->label('Note de vérification')
                                    ->content('Les virements et les montants élevés doivent être rapprochés chaque jour.'),
                            ]),
                        Section::make('Notes administratives')
                            ->description('Conservez le contexte interne et les commentaires de rapprochement.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpanFull()
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(5)
                                    ->placeholder('Ajoutez les confirmations bancaires, anomalies ou remarques de recouvrement...'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label(__('erp.common.transaction'))
                    ->state(fn(Payment $record): string => $record->reference ?: ('PAY-' . str_pad((string) $record->getKey(), 4, '0', STR_PAD_LEFT)))
                    ->description(fn(Payment $record): string => (optional($record->payment_date)->format('M d, Y') ?? __('erp.common.none')) . ' • ' . __('erp.payment_methods.' . $record->payment_method))
                    ->searchable(['reference'])
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label(__('erp.common.client'))
                    ->state(fn(Payment $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: __('erp.common.account_client'))
                    ->description(fn(Payment $record): string => $record->invoice?->invoice_number
                        ? __('erp.resources.payment.linked_to_invoice', ['invoice' => $record->invoice->invoice_number])
                        : __('erp.resources.payment.awaiting_reconciliation'))
                    ->searchable(['reference']),
                TextColumn::make('amount')
                    ->label(__('erp.common.amount'))
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->description(fn(Payment $record): string => $record->allow_overpayment ? __('erp.resources.payment.overpayment_allowed') : __('erp.resources.payment.standard_settlement'))
                    ->sortable(),
                TextColumn::make('period_lock_status')
                    ->label('Période')
                    ->state(fn(Payment $record): string => static::lockStatusLabel($record))
                    ->badge()
                    ->color(fn(Payment $record): string => static::lockStatusColor($record)),
                TextColumn::make('invoice_due_date')
                    ->label(__('erp.common.due_date'))
                    ->state(fn(Payment $record): string => optional($record->invoice?->due_date)->format('M d, Y') ?? __('erp.resources.payment.not_scheduled'))
                    ->description(fn(Payment $record): string => $record->invoice?->due_date?->isPast() ? __('erp.resources.payment.invoice_overdue') : __('erp.resources.payment.finance_calendar')),
                TextColumn::make('payment_method')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => __('erp.payment_methods.' . $state)),
                TextColumn::make('reconciliation_state')
                    ->label(__('erp.common.reconciliation'))
                    ->state(fn(Payment $record): string => __('erp.resources.payment.reconciliation.' . $record->reconciliationState()))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        __('erp.resources.payment.reconciliation.completed') => 'success',
                        __('erp.resources.payment.reconciliation.flagged') => 'danger',
                        default => 'warning',
                    }),
                IconColumn::make('is_flagged')
                    ->label('Signalé')
                    ->boolean()
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options(trans('erp.payment_methods')),
                TernaryFilter::make('is_flagged')
                    ->label('Signalement')
                    ->trueLabel('Signalés uniquement')
                    ->falseLabel('Non signalés uniquement')
                    ->placeholder('Tous'),
            ])
            ->recordActions([
                Action::make('reconcile')
                    ->label(__('erp.actions.reconcile'))
                    ->visible(fn(Payment $record): bool => $record->invoice_id === null && (auth()->user()?->can('payments.update') ?? false))
                    ->action(function (Payment $record): void {
                        $matched = $record->reconcileAgainstOpenInvoice();

                        $notification = Notification::make()
                            ->title($matched ? __('erp.resources.payment.reconciled') : __('erp.resources.payment.no_match_found'));

                        if ($matched) {
                            $notification->success();
                        } else {
                            $notification->warning();
                        }

                        $notification->send();
                    }),
                Action::make('flag')
                    ->label(fn(Payment $record): string => $record->is_flagged ? 'Retirer le signalement' : __('erp.actions.flag'))
                    ->color(fn(Payment $record): string => $record->is_flagged ? 'warning' : 'danger')
                    ->visible(fn(): bool => auth()->user()?->can('payments.update') ?? false)
                    ->form(fn(Payment $record): array => $record->is_flagged ? [] : [
                        Textarea::make('reason')
                            ->label('Motif du signalement')
                            ->placeholder('Ex: paiement en double, montant suspect...')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->requiresConfirmation(fn(Payment $record): bool => $record->is_flagged)
                    ->modalHeading(fn(Payment $record): string => $record->is_flagged ? 'Retirer le signalement ?' : 'Signaler ce paiement')
                    ->action(function (Payment $record, array $data): void {
                        $userId = (int) auth()->id();

                        if ($record->is_flagged) {
                            $record->unflag($userId);
                            Notification::make()
                                ->title('Signalement retiré')
                                ->body('Le paiement ' . ($record->reference ?: __('erp.common.transaction')) . ' n\'est plus signalé.')
                                ->success()
                                ->send();
                        } else {
                            $record->flag($data['reason'] ?? '', $userId);
                            Notification::make()
                                ->title(__('erp.resources.payment.flagged_notification', ['reference' => $record->reference ?: __('erp.common.transaction')]))
                                ->body('Motif : ' . ($data['reason'] ?? ''))
                                ->warning()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
        return $record instanceof Payment && FinancialPeriod::isDateLocked($record->payment_date);
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

    protected static function formatMoney(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 2, '.', ' ');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
