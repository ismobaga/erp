<?php

namespace Crommix\HR\Filament\Resources\LeaveRequests;

use BackedEnum;
use Crommix\HR\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use Crommix\HR\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use Crommix\HR\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use Crommix\HR\Models\Employee;
use Crommix\HR\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|\UnitEnum|null $navigationGroup = 'HR';

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'type';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->label('Employee')
                ->options(fn (): array => Employee::query()->orderBy('first_name')->get()->pluck('full_name', 'id')->all())
                ->searchable()
                ->preload()
                ->required(),
            Select::make('type')
                ->label('Leave Type')
                ->options([
                    'annual' => 'Annual Leave',
                    'sick' => 'Sick Leave',
                    'unpaid' => 'Unpaid Leave',
                    'maternity' => 'Maternity Leave',
                    'paternity' => 'Paternity Leave',
                    'other' => 'Other',
                ])
                ->native(false)
                ->default('annual')
                ->required(),
            DatePicker::make('starts_at')
                ->label('Start Date')
                ->required(),
            DatePicker::make('ends_at')
                ->label('End Date')
                ->required()
                ->afterOrEqual('starts_at'),
            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->native(false)
                ->default('pending')
                ->required(),
            Textarea::make('reason')
                ->label('Reason')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('rejection_reason')
                ->label('Rejection Reason')
                ->rows(2)
                ->columnSpanFull()
                ->visible(fn ($get): bool => $get('status') === 'rejected'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn (LeaveRequest $record): string => $record->employee?->full_name ?? '—')
                    ->searchable(['hr_employees.first_name', 'hr_employees.last_name'])
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'annual' => 'Annual',
                        'sick' => 'Sick',
                        'unpaid' => 'Unpaid',
                        'maternity' => 'Maternity',
                        'paternity' => 'Paternity',
                        default => ucfirst($state),
                    }),
                TextColumn::make('starts_at')->label('From')->date('d/m/Y')->sortable(),
                TextColumn::make('ends_at')->label('To')->date('d/m/Y')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('approver.name')->label('Approved By')->toggleable(),
                TextColumn::make('approved_at')->label('Decided At')->dateTime('d/m/Y')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('type')
                    ->label('Leave Type')
                    ->options([
                        'annual' => 'Annual Leave',
                        'sick' => 'Sick Leave',
                        'unpaid' => 'Unpaid Leave',
                        'maternity' => 'Maternity Leave',
                        'paternity' => 'Paternity Leave',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record): bool => $record->status === 'pending')
                    ->action(function (LeaveRequest $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record): bool => $record->status === 'pending')
                    ->action(function (LeaveRequest $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
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
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
