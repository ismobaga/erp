<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Ledger';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Invoice identity')
                            ->description('Create and dispatch a formal receivable record linked to the operational ledger.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Invoice number')
                                    ->default(fn(): string => static::generateInvoiceNumber())
                                    ->readOnly()
                                    ->required(),
                                Select::make('client_id')
                                    ->label('Client entity')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload()
                                    ->required(),
                                Select::make('quote_id')
                                    ->label('Linked quote')
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
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->default(now()->addDays(30)),
                            ]),
                        Section::make('Ledger status')
                            ->description('Track collection state and payment urgency.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'partially_paid' => 'Partially paid',
                                        'paid' => 'Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('collection_brief')
                                    ->label('Collection brief')
                                    ->content('Monitor receivables, client follow-up, and overdue exposure from this panel.'),
                            ]),
                        Section::make('Invoice line items')
                            ->description('Compose the billable scope with services, quantities, and unit pricing.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->defaultItems(1)
                                    ->addActionLabel('Add line item')
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
                                            ->label('Line total')
                                            ->content(fn(Get $get): string => static::formatMoney(((float) ($get('quantity') ?? 0)) * ((float) ($get('unit_price') ?? 0)))),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Billing notes')
                            ->description('Store reminders, dispatch notes, and collection guidance.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Administrative notes')
                                    ->rows(6)
                                    ->placeholder('Include payment instructions, internal context, or contract references...'),
                            ]),
                        Section::make('Financial summary')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 5])
                            ->schema([
                                TextInput::make('discount_total')
                                    ->label('Discount total')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                TextInput::make('tax_total')
                                    ->label('Tax total')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                Placeholder::make('subtotal_preview')
                                    ->label('Subtotal')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['subtotal'])),
                                Placeholder::make('grand_total_preview')
                                    ->label('Total receivable')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['total'])),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice no.')
                    ->searchable(),
                TextColumn::make('client_name')
                    ->label('Client entity')
                    ->state(fn(Invoice $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Client account'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('issue_date')
                    ->label('Issue date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total amount')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance due')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'partially_paid' => 'Partially paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
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
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function generateInvoiceNumber(): string
    {
        $next = (Invoice::max('id') ?? 0) + 1;

        return 'INV-' . now()->format('Y') . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    protected static function calculateTotals(array $items, float $discount, float $tax): array
    {
        $subtotal = collect($items)->sum(fn(array $item): float => ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)));

        return [
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount + $tax),
        ];
    }

    protected static function formatMoney(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 2, '.', ' ');
    }
}
