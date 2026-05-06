<?php

namespace App\Filament\Resources\Whatsapp;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Whatsapp\Pages\CreateWhatsappTemplate;
use App\Filament\Resources\Whatsapp\Pages\EditWhatsappTemplate;
use App\Filament\Resources\Whatsapp\Pages\ListWhatsappTemplates;
use App\Models\WhatsappTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsappTemplateResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'settings';

    protected static ?string $model = WhatsappTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationLabel = 'Modèles';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Modèle WhatsApp')->schema([
                TextInput::make('name')
                    ->label('Nom du modèle')
                    ->required()
                    ->maxLength(100),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'invoice'   => 'Facture',
                        'quote'     => 'Devis',
                        'reminder'  => 'Rappel',
                        'dunning'   => 'Relance (dunning)',
                        'custom'    => 'Personnalisé',
                    ])
                    ->required()
                    ->default('custom'),
                Textarea::make('body')
                    ->label('Corps du message')
                    ->helperText('Utilisez {variable} pour les variables dynamiques, ex: {client}, {number}, {amount}.')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                TagsInput::make('variables')
                    ->label('Variables disponibles')
                    ->placeholder('client, number, amount …')
                    ->helperText('Liste des noms de variables utilisées dans le corps du modèle.')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->label('Type'),
                TextColumn::make('body')
                    ->limit(60)
                    ->label('Corps'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Actif'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Modifié le'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'invoice'  => 'Facture',
                        'quote'    => 'Devis',
                        'reminder' => 'Rappel',
                        'dunning'  => 'Relance',
                        'custom'   => 'Personnalisé',
                    ])
                    ->label('Type'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListWhatsappTemplates::route('/'),
            'create' => CreateWhatsappTemplate::route('/create'),
            'edit'   => EditWhatsappTemplate::route('/{record}/edit'),
        ];
    }
}
