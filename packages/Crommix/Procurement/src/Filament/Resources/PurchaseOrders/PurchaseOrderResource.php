<?php

namespace Crommix\Procurement\Filament\Resources\PurchaseOrders;

use BackedEnum;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use Crommix\Procurement\Models\PurchaseOrder;
use Crommix\Procurement\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
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
        ]);
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
                EditAction::make(),
                DeleteAction::make(),
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
