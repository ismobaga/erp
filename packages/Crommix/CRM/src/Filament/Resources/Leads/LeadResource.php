<?php

namespace Crommix\CRM\Filament\Resources\Leads;

use App\Models\User;
use BackedEnum;
use Crommix\CRM\Filament\Resources\Leads\Pages\CreateLead;
use Crommix\CRM\Filament\Resources\Leads\Pages\EditLead;
use Crommix\CRM\Filament\Resources\Leads\Pages\ListLeads;
use Crommix\CRM\Models\Lead;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Leads';

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
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50),
            TextInput::make('company_name')
                ->label('Company')
                ->maxLength(255),
            Select::make('source')
                ->label('Source')
                ->options([
                    'website'  => 'Website',
                    'referral' => 'Referral',
                    'social'   => 'Social Media',
                    'ads'      => 'Advertising',
                    'other'    => 'Other',
                ])
                ->native(false),
            Select::make('status')
                ->label('Status')
                ->options([
                    'new'       => 'New',
                    'contacted' => 'Contacted',
                    'qualified' => 'Qualified',
                    'converted' => 'Converted',
                    'lost'      => 'Lost',
                ])
                ->native(false)
                ->default('new')
                ->required(),
            TextInput::make('estimated_value')
                ->label('Estimated Value')
                ->numeric()
                ->prefix('$'),
            Select::make('assigned_to')
                ->label('Assigned To')
                ->options(fn(): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')->label('First Name')->searchable()->sortable(),
                TextColumn::make('last_name')->label('Last Name')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('company_name')->label('Company')->searchable()->toggleable(),
                TextColumn::make('source')->label('Source')->badge(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new'       => 'info',
                        'contacted' => 'warning',
                        'qualified' => 'primary',
                        'converted' => 'success',
                        'lost'      => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('estimated_value')->label('Value')->money('USD')->sortable(),
                TextColumn::make('assignee.name')->label('Assigned To')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new'       => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'converted' => 'Converted',
                        'lost'      => 'Lost',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'website'  => 'Website',
                        'referral' => 'Referral',
                        'social'   => 'Social Media',
                        'ads'      => 'Advertising',
                        'other'    => 'Other',
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
            'index'  => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit'   => EditLead::route('/{record}/edit'),
        ];
    }
}
