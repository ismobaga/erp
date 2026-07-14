<?php

namespace Crommix\Procurement\Filament\Resources\PurchaseOrders;

use BackedEnum;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use Crommix\Inventory\Models\Product;
use Crommix\Inventory\Models\Warehouse;
use Crommix\Procurement\Models\PurchaseOrder;
use Crommix\Procurement\Models\Supplier;
use Crommix\Procurement\Services\ProcurementService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.procurement', config('procurement.enabled', true));
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
            Select::make('supplier_id')
                ->label('Supplier')
                ->options(fn(): array => Supplier::active()->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('reference')
                ->label('Reference')
                ->maxLength(255),
            Select::make('status')
                ->label('Status')
                ->options([
                    'draft'     => 'Draft',
                    'submitted' => 'Submitted',
                    'approved'  => 'Approved',
                    'received'  => 'Received',
                    'cancelled' => 'Cancelled',
                ])
                ->native(false)
                ->default('draft')
                ->required(),
            DatePicker::make('order_date')
                ->label('Order Date')
                ->default(now())
                ->required(),
            DatePicker::make('expected_date')
                ->label('Expected Delivery'),
            TextInput::make('currency')
                ->label('Currency')
                ->default('USD')
                ->maxLength(3),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
            Repeater::make('items')
                ->label('Line Items')
                ->relationship('items')
                ->columnSpanFull()
                ->columns(4)
                ->defaultItems(0)
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->placeholder('— service / free text —')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $product = $state ? Product::query()->find($state) : null;

                            if ($product !== null) {
                                $set('description', $product->name);
                                $set('unit_price', (string) $product->cost_price);
                            }
                        }),
                    TextInput::make('description')
                        ->label('Description')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->minValue(0.001),
                    TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0),
                ])
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::withComputedTotal($data))
                ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::withComputedTotal($data)),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function withComputedTotal(array $data): array
    {
        $data['total_price'] = round((float) ($data['quantity'] ?? 0) * (float) ($data['unit_price'] ?? 0), 2);

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Reference')->searchable()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->sortable(),
                TextColumn::make('order_date')->label('Order Date')->date('d/m/Y')->sortable(),
                TextColumn::make('expected_date')->label('Expected')->date('d/m/Y')->sortable(),
                TextColumn::make('total_amount')->label('Total')->money('USD')->sortable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved'  => 'success',
                        'submitted' => 'warning',
                        'received'  => 'info',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'submitted' => 'Submitted',
                        'approved'  => 'Approved',
                        'received'  => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['submitted', 'approved'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Receive purchase order?')
                    ->modalDescription('Outstanding quantities of product-linked items will be added to stock. Service lines are marked received without stock movement.')
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(fn (): array => Warehouse::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->placeholder('—'),
                        Textarea::make('receive_notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function (PurchaseOrder $record, array $data, ProcurementService $procurementService): void {
                        try {
                            $procurementService->receive(
                                $record,
                                $data['warehouse_id'] ? (int) $data['warehouse_id'] : null,
                                $data['receive_notes'] ?? null,
                            );
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot receive order.')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Purchase order received.')
                            ->body('Stock has been updated for all product-linked lines.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['draft', 'cancelled'], true)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit'   => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
