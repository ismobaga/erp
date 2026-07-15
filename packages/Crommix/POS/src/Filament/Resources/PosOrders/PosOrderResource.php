<?php

namespace Crommix\POS\Filament\Resources\PosOrders;

use BackedEnum;
use Crommix\POS\Filament\Resources\PosOrders\Pages\CreatePosOrder;
use Crommix\POS\Filament\Resources\PosOrders\Pages\EditPosOrder;
use Crommix\POS\Filament\Resources\PosOrders\Pages\ListPosOrders;
use Crommix\Inventory\Models\Product;
use Crommix\POS\Models\PosOrder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PosOrderResource extends Resource
{
    protected static ?string $model = PosOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static string|\UnitEnum|null $navigationGroup = 'POS';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.pos', config('pos.enabled', true));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isModuleEnabled() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return static::isModuleEnabled() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_number')
                ->label('Order Number')
                ->maxLength(255),
            Select::make('payment_method')
                ->label('Payment Method')
                ->options([
                    'cash'   => 'Cash',
                    'card'   => 'Card',
                    'mobile' => 'Mobile Payment',
                    'other'  => 'Other',
                ])
                ->native(false)
                ->default('cash')
                ->required(),
            TextInput::make('amount_paid')
                ->label('Amount Paid')
                ->numeric()
                ->minValue(0),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(2),
            // Sales are processed through PosService (stock deduction + ledger
            // posting), so items are captured here and handed to the service —
            // totals and status are computed, never typed.
            Repeater::make('items')
                ->label('Items')
                ->columnSpanFull()
                ->columns(4)
                ->defaultItems(1)
                ->required()
                ->visibleOn('create')
                ->dehydrated()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn (): array => Product::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->placeholder('— free line —')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $product = $state ? Product::query()->find($state) : null;

                            if ($product !== null) {
                                $set('name', $product->name);
                                $set('unit_price', (string) $product->sale_price);
                            }
                        }),
                    TextInput::make('name')
                        ->label('Description')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->minValue(1),
                    TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Order #')->searchable()->sortable(),
                TextColumn::make('session.opened_at')->label('Session')->date('d/m/Y')->sortable(),
                TextColumn::make('payment_method')->label('Payment')->badge(),
                TextColumn::make('total_amount')->label('Total')->money('USD')->sortable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending'   => 'warning',
                        'refunded'  => 'info',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'completed' => 'Completed',
                        'refunded'  => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'   => 'Cash',
                        'card'   => 'Card',
                        'mobile' => 'Mobile Payment',
                        'other'  => 'Other',
                    ]),
            ])
            ->recordActions([
                // Completed orders have deducted stock and posted to the
                // ledger — they must not be silently edited or deleted.
                EditAction::make()
                    ->visible(fn (PosOrder $record): bool => $record->status !== 'completed'),
                DeleteAction::make()
                    ->visible(fn (PosOrder $record): bool => $record->status !== 'completed'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPosOrders::route('/'),
            'create' => CreatePosOrder::route('/create'),
            'edit'   => EditPosOrder::route('/{record}/edit'),
        ];
    }
}
