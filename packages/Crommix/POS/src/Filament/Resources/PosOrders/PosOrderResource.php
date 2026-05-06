<?php

namespace Crommix\POS\Filament\Resources\PosOrders;

use BackedEnum;
use Crommix\POS\Filament\Resources\PosOrders\Pages\CreatePosOrder;
use Crommix\POS\Filament\Resources\PosOrders\Pages\EditPosOrder;
use Crommix\POS\Filament\Resources\PosOrders\Pages\ListPosOrders;
use Crommix\POS\Models\PosOrder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
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
            Select::make('status')
                ->label('Status')
                ->options([
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'refunded'  => 'Refunded',
                    'cancelled' => 'Cancelled',
                ])
                ->native(false)
                ->default('pending')
                ->required(),
            TextInput::make('total_amount')
                ->label('Total')
                ->numeric()
                ->prefix('$'),
            TextInput::make('amount_paid')
                ->label('Amount Paid')
                ->numeric()
                ->prefix('$'),
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
            'index'  => ListPosOrders::route('/'),
            'create' => CreatePosOrder::route('/create'),
            'edit'   => EditPosOrder::route('/{record}/edit'),
        ];
    }
}
