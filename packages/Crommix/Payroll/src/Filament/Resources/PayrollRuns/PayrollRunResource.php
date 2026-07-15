<?php

namespace Crommix\Payroll\Filament\Resources\PayrollRuns;

use BackedEnum;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\CreatePayrollRun;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\EditPayrollRun;
use Crommix\Payroll\Filament\Resources\PayrollRuns\Pages\ListPayrollRuns;
use Crommix\Payroll\Models\PayrollRun;
use Crommix\Payroll\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.payroll', config('payroll.enabled', true));
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
            TextInput::make('period_month')
                ->label('Period (YYYY-MM)')
                ->placeholder('2026-05')
                ->required()
                ->maxLength(7),
            Select::make('status')
                ->label('Status')
                // 'completed' is intentionally absent: completion goes through
                // the Complete action so the run is posted to the ledger.
                ->options([
                    'draft'      => 'Draft',
                    'processing' => 'Processing',
                    'cancelled'  => 'Cancelled',
                ])
                ->native(false)
                ->default('draft')
                ->disabled(fn (?PayrollRun $record): bool => $record?->status === 'completed')
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
                Action::make('generateItems')
                    ->label('Generate items')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->visible(fn (PayrollRun $record): bool => $record->status === 'draft' && ! $record->items()->exists())
                    ->requiresConfirmation()
                    ->modalHeading('Generate payroll items?')
                    ->modalDescription('One line per active employee will be created from their base salary.')
                    ->action(function (PayrollRun $record, PayrollService $payrollService): void {
                        $run = $payrollService->generateItems($record);

                        Notification::make()
                            ->title('Payroll items generated.')
                            ->body($run->items()->count().' employee line(s) — gross total: '.number_format((float) $run->total_gross))
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PayrollRun $record): bool => in_array($record->status, ['draft', 'processing'], true)
                        && $record->items()->exists())
                    ->requiresConfirmation()
                    ->modalHeading('Complete this payroll run?')
                    ->modalDescription('The run will be locked and the salary expense posted to the general ledger.')
                    ->action(function (PayrollRun $record, PayrollService $payrollService): void {
                        $payrollService->complete($record, (int) auth()->id());

                        Notification::make()
                            ->title('Payroll run completed.')
                            ->body('Salary expense posted to the ledger.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (PayrollRun $record): bool => $record->status !== 'completed'),
                DeleteAction::make()
                    ->visible(fn (PayrollRun $record): bool => $record->status !== 'completed'),
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
