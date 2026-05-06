<?php

namespace Crommix\HR\Filament\Resources\LeaveTypes;

use BackedEnum;
use Crommix\HR\Filament\Resources\LeaveTypes\Pages\CreateLeaveType;
use Crommix\HR\Filament\Resources\LeaveTypes\Pages\EditLeaveType;
use Crommix\HR\Filament\Resources\LeaveTypes\Pages\ListLeaveTypes;
use Crommix\HR\Models\LeaveType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'HR';

    protected static ?string $navigationLabel = 'Leave Types';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Code')
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            TextInput::make('days_per_year')
                ->label('Days Per Year')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Toggle::make('is_paid')
                ->label('Paid Leave')
                ->default(true),
            Toggle::make('requires_approval')
                ->label('Requires Approval')
                ->default(true),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->badge()->toggleable(),
                TextColumn::make('days_per_year')->label('Days/Year')->sortable(),
                IconColumn::make('is_paid')->label('Paid')->boolean(),
                IconColumn::make('requires_approval')->label('Approval Req.')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_paid')->label('Paid'),
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
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }
}
