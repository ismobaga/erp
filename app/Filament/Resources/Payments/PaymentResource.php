<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Client;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Ledger';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Payments Tracking';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Transaction identity')
                            ->description('Record fiscal inflows and link them to outstanding invoice obligations.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                DatePicker::make('payment_date')
                                    ->label('Payment date')
                                    ->default(now())
                                    ->required(),
                                Select::make('invoice_id')
                                    ->label('Linked invoice')
                                    ->relationship('invoice', 'invoice_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->helperText('Optional at entry time. Use smart-link later if finance records the payment before matching it.')
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
                                    ->label('Method')
                                    ->options([
                                        'bank_transfer' => 'Bank transfer',
                                        'cash' => 'Cash',
                                        'check' => 'Check',
                                        'mobile_money' => 'Mobile money',
                                    ])
                                    ->default('bank_transfer')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('reference')
                                    ->label('Reference #')
                                    ->placeholder('BT-9902-XQ')
                                    ->columnSpanFull(),
                                TextInput::make('amount')
                                    ->label('Received amount')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->minValue(0.01)
                                    ->required(),
                            ]),
                        Section::make('Settlement controls')
                            ->description('Apply validation logic and reconciliation exceptions.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Toggle::make('allow_overpayment')
                                    ->label('Allow overpayment')
                                    ->helperText('Only enable when finance explicitly approves the excess receipt.'),
                                Placeholder::make('tracking_notice')
                                    ->label('Verification note')
                                    ->content('Bank transfers and high-value entries should be reconciled daily.'),
                            ]),
                        Section::make('Administrative notes')
                            ->description('Store internal settlement context and reconciliation comments.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpanFull()
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(5)
                                    ->placeholder('Include bank confirmation details, anomalies, or collection remarks...'),
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
                    ->label('Transaction')
                    ->state(fn(Payment $record): string => $record->reference ?: ('PAY-' . str_pad((string) $record->getKey(), 4, '0', STR_PAD_LEFT)))
                    ->description(fn(Payment $record): string => (optional($record->payment_date)->format('M d, Y') ?? 'Undated') . ' • ' . str($record->payment_method)->replace('_', ' ')->title())
                    ->searchable(['reference'])
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->state(fn(Payment $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Client account')
                    ->description(fn(Payment $record): string => $record->invoice?->invoice_number ? 'Linked to ' . $record->invoice->invoice_number : 'Awaiting invoice match')
                    ->searchable(['reference']),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->description(fn(Payment $record): string => $record->allow_overpayment ? 'Overpayment override enabled' : 'Standard settlement')
                    ->sortable(),
                TextColumn::make('invoice_due_date')
                    ->label('Due date')
                    ->state(fn(Payment $record): string => optional($record->invoice?->due_date)->format('M d, Y') ?? 'Not scheduled')
                    ->description(fn(Payment $record): string => $record->invoice?->due_date?->isPast() ? 'Overdue exposure' : 'Finance schedule'),
                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('reconciliation_state')
                    ->label('Reconciliation')
                    ->state(fn(Payment $record): string => match ($record->reconciliationState()) {
                        'completed' => 'Completed',
                        'flagged' => 'Flagged',
                        default => 'Pending',
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Flagged' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank transfer',
                        'cash' => 'Cash',
                        'check' => 'Check',
                        'mobile_money' => 'Mobile money',
                    ]),
            ])
            ->recordActions([
                Action::make('reconcile')
                    ->label('Smart-link')
                    ->visible(fn(Payment $record): bool => $record->invoice_id === null)
                    ->action(function (Payment $record): void {
                        $matched = $record->reconcileAgainstOpenInvoice();

                        $notification = Notification::make()
                            ->title($matched ? 'Payment reconciled to an open invoice.' : 'No eligible invoice match was found for this payment.');

                        if ($matched) {
                            $notification->success();
                        } else {
                            $notification->warning();
                        }

                        $notification->send();
                    }),
                Action::make('flag')
                    ->label('Flag')
                    ->color('danger')
                    ->action(fn(Payment $record) => Notification::make()->title(($record->reference ?: 'Payment entry') . ' has been flagged for finance review.')->warning()->send()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
