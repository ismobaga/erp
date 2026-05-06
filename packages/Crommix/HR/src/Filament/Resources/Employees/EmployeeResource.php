<?php

namespace Crommix\HR\Filament\Resources\Employees;

use BackedEnum;
use Crommix\HR\Filament\Resources\Employees\Pages\CreateEmployee;
use Crommix\HR\Filament\Resources\Employees\Pages\EditEmployee;
use Crommix\HR\Filament\Resources\Employees\Pages\ListEmployees;
use Crommix\HR\Models\Department;
use Crommix\HR\Models\Employee;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'HR';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')
                ->label('First Name')
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label('Last Name')
                ->required()
                ->maxLength(255),
            TextInput::make('employee_number')
                ->label('Employee Number')
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50),
            TextInput::make('position')
                ->label('Position')
                ->maxLength(255),
            Select::make('department_id')
                ->label('Department')
                ->options(fn(): array => Department::query()->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            Select::make('employment_type')
                ->label('Employment Type')
                ->options([
                    'full_time'  => 'Full Time',
                    'part_time'  => 'Part Time',
                    'contractor' => 'Contractor',
                ])
                ->native(false)
                ->default('full_time')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'active'     => 'Active',
                    'inactive'   => 'Inactive',
                    'terminated' => 'Terminated',
                ])
                ->native(false)
                ->default('active')
                ->required(),
            DatePicker::make('hired_at')
                ->label('Hire Date'),
            TextInput::make('base_salary')
                ->label('Base Salary')
                ->numeric()
                ->prefix('$'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')->label('#')->searchable()->sortable(),
                TextColumn::make('first_name')->label('First Name')->searchable()->sortable(),
                TextColumn::make('last_name')->label('Last Name')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Department')->toggleable(),
                TextColumn::make('position')->label('Position')->searchable()->toggleable(),
                TextColumn::make('employment_type')->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'full_time'  => 'success',
                        'part_time'  => 'warning',
                        'contractor' => 'info',
                        default      => 'gray',
                    }),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active'     => 'success',
                        'inactive'   => 'warning',
                        'terminated' => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('hired_at')->label('Hired')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'     => 'Active',
                        'inactive'   => 'Inactive',
                        'terminated' => 'Terminated',
                    ]),
                SelectFilter::make('employment_type')
                    ->label('Employment Type')
                    ->options([
                        'full_time'  => 'Full Time',
                        'part_time'  => 'Part Time',
                        'contractor' => 'Contractor',
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
            'index'  => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit'   => EditEmployee::route('/{record}/edit'),
        ];
    }
}
