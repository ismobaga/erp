<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use BackedEnum;
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
                                    })
                                    ->required(),
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
                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->state(fn(Payment $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Client account')
                    ->searchable(['reference']),
                TextColumn::make('reference')
                    ->label('Reference #')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('invoice.invoice_number')
                    ->label('Linked invoice')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state): string => 'FCFA ' . number_format((float) $state, 2, '.', ' '))
                    ->sortable(),
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
