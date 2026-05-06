<?php

namespace App\Filament\Resources\Whatsapp;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Whatsapp\Pages\ListWhatsappConversations;
use App\Filament\Resources\Whatsapp\Pages\ViewWhatsappConversation;
use App\Models\User;
use App\Models\WhatsappConversation;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsappConversationResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'settings';

    protected static ?string $model = WhatsappConversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Conversations';

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
                TextColumn::make('displayName')
                    ->label('Contact')
                    ->getStateUsing(fn(WhatsappConversation $record): string => $record->displayName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($q) use ($search): void {
                            $q->where('chat_id', 'like', "%{$search}%")
                              ->orWhere('contact_name', 'like', "%{$search}%")
                              ->orWhereHas('client', fn($cq) => $cq->where('company_name', 'like', "%{$search}%")
                                  ->orWhere('contact_name', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('chat_id')
                    ->label('JID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client.company_name')
                    ->label('Client')
                    ->formatStateUsing(fn(?string $state, WhatsappConversation $record): string => $state ?: ($record->client?->contact_name ?? '-'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open'   => 'success',
                        'closed' => 'gray',
                        default  => 'warning',
                    })
                    ->label('Statut'),
                TextColumn::make('assignedUser.name')
                    ->label('Assigné à')
                    ->default('-'),
                TextColumn::make('last_message_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Dernier message'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open'   => 'Ouverte',
                        'closed' => 'Fermée',
                    ])
                    ->label('Statut'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('assign')
                    ->label('Assigner')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->form([
                        Select::make('assigned_to')
                            ->label('Assigner à')
                            ->options(fn(): array => User::query()->where('status', 'active')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->nullable(),
                    ])
                    ->action(fn(WhatsappConversation $record, array $data) => $record->update(['assigned_to' => $data['assigned_to']])),
                Action::make('close')
                    ->label('Fermer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(WhatsappConversation $record): bool => $record->status === 'open')
                    ->action(fn(WhatsappConversation $record) => $record->update(['status' => 'closed'])),
                Action::make('reopen')
                    ->label('Rouvrir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(WhatsappConversation $record): bool => $record->status === 'closed')
                    ->action(fn(WhatsappConversation $record) => $record->update(['status' => 'open'])),
            ])
            ->defaultSort('last_message_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappConversations::route('/'),
            'view'  => ViewWhatsappConversation::route('/{record}'),
        ];
    }
}
