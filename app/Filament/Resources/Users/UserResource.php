<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Staff Directory';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Staff identity')
                            ->description('Manage your workforce, collaborators, and administrative contacts across the ERP ecosystem.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full name')
                                    ->required(),
                                TextInput::make('email')
                                    ->label('Email address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                TextInput::make('phone')
                                    ->label('Phone number')
                                    ->tel(),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'away' => 'Away',
                                        'offline' => 'Offline',
                                        'restricted' => 'Restricted',
                                    ])
                                    ->default('active')
                                    ->native(false)
                                    ->required(),
                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Access controls')
                            ->description('Credential and operational access management.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn($state): bool => filled($state))
                                    ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                                    ->required(fn(string $operation): bool => $operation === 'create'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Collaborator')
                    ->description(fn(User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('access_tier')
                    ->label('Access tier')
                    ->state(fn(User $record): string => static::resolveAccessTier($record))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Admin Access' => 'primary',
                        'Restricted' => 'danger',
                        'Contractor' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('role_summary')
                    ->label('Permissions')
                    ->state(fn(User $record): string => $record->getRoleNames()->join(', ') ?: 'Standard workspace')
                    ->wrap(),
                TextColumn::make('phone')
                    ->label('Operational contact')
                    ->placeholder('No direct line'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'away' => 'warning',
                        'restricted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('last_login_at')
                    ->label('Last active')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'away' => 'Away',
                        'offline' => 'Offline',
                        'restricted' => 'Restricted',
                    ]),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function resolveAccessTier(User $record): string
    {
        $roles = $record->getRoleNames()->map(fn(string $role): string => str($role)->lower()->toString());

        if ($record->status === 'restricted') {
            return 'Restricted';
        }

        if ($roles->contains(fn(string $role): bool => str_contains($role, 'admin'))) {
            return 'Admin Access';
        }

        if ($roles->contains(fn(string $role): bool => str_contains($role, 'contract'))) {
            return 'Contractor';
        }

        return 'Standard';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
