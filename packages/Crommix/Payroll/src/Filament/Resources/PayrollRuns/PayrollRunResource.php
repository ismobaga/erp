<?php

namespace Crommix\Payroll\Filament\Resources\PayrollRuns;

use BackedEnum;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\CreatePayrollRun;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\EditPayrollRun;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\ListPayrollRuns;
use Crommix\Payroll\Models\PayrollRun;
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

class PayrollRunResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Payroll Runs';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'period_month';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('period_month')
                ->label('Period (YYYY-MM)')
                ->placeholder('2026-05')
                ->required()
                ->maxLength(7),
            Select::make('status')
                ->label('Status')
                ->options([
                    'draft'      => 'Draft',
                    'processing' => 'Processing',
                    'completed'  => 'Completed',
                    'cancelled'  => 'Cancelled',
                ])
                ->native(false)
                ->default('draft')
                ->required(),
            TextInput::make('reference')
                ->label('Reference')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_month')->label('Period')->sortable()->searchable(),
                TextColumn::make('reference')->label('Reference')->searchable(),
                TextColumn::make('total_gross')->label('Gross')->money('USD')->sortable(),
                TextColumn::make('total_deductions')->label('Deductions')->money('USD'),
                TextColumn::make('total_net')->label('Net')->money('USD')->sortable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed'  => 'success',
                        'processing' => 'warning',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('processed_at')->label('Processed')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'      => 'Draft',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
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
            'index'  => ListPayrollRuns::route('/'),
            'create' => CreatePayrollRun::route('/create'),
            'edit'   => EditPayrollRun::route('/{record}/edit'),
        ];
    }
}
