<?php

namespace Crommix\CRM\Filament\Resources\Contacts;

use App\Models\User;
use BackedEnum;
use Crommix\CRM\Filament\Resources\Contacts\Pages\CreateContact;
use Crommix\CRM\Filament\Resources\Contacts\Pages\EditContact;
use Crommix\CRM\Filament\Resources\Contacts\Pages\ListContacts;
use Crommix\CRM\Models\Contact;
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

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Contacts';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.crm', config('crm.enabled', true));
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
            TextInput::make('first_name')
                ->label('First Name')
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label('Last Name')
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->maxLength(50),
            TextInput::make('job_title')
                ->label('Job Title')
                ->maxLength(255),
            TextInput::make('company_name')
                ->label('Company')
                ->maxLength(255),
            Select::make('assigned_to')
                ->label('Assigned To')
                ->options(fn (): array => User::query()
                    ->whereHas('companies', fn ($q) => $q->where('companies.id', currentCompany()?->id))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->placeholder('—'),
            Textarea::make('address')
                ->label('Address')
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->state(fn (Contact $record): string => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),
                TextColumn::make('company_name')->label('Company')->searchable()->toggleable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Phone')->toggleable(),
                TextColumn::make('job_title')->label('Title')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead.status')
                    ->label('Origin')
                    ->placeholder('Direct')
                    ->formatStateUsing(fn (): string => 'Lead')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('assignee.name')->label('Assigned')->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('Created')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignee', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit'   => EditContact::route('/{record}/edit'),
        ];
    }
}
