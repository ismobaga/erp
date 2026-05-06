<?php

namespace App\Filament\Resources\Whatsapp;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Whatsapp\Pages\ListWhatsappMessageLogs;
use App\Filament\Resources\Whatsapp\Pages\ViewWhatsappMessageLog;
use App\Models\WhatsappMessageLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsappMessageLogResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'settings';

    protected static ?string $model = WhatsappMessageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Journal WhatsApp';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.company_name')
                    ->label('Client')
                    ->formatStateUsing(fn(?string $state, WhatsappMessageLog $record): string => $state ?: ($record->client?->contact_name ?? '-'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->label('Type'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->label('Statut'),
                TextColumn::make('message')
                    ->limit(50)
                    ->label('Message'),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Envoyé le'),
                TextColumn::make('error_message')
                    ->limit(40)
                    ->label('Erreur'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'En attente',
                        'sent' => 'Envoyé',
                        'failed' => 'Échoué',
                    ])
                    ->label('Statut'),
                SelectFilter::make('type')
                    ->options([
                        'text' => 'Texte',
                        'file' => 'Fichier',
                    ])
                    ->label('Type'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappMessageLogs::route('/'),
            'view' => ViewWhatsappMessageLog::route('/{record}'),
        ];
    }
}
