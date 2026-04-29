<?php

namespace App\Filament\Resources\ContactRequests;

use App\Filament\Resources\ContactRequests\Pages\ListContactRequests;
use App\Filament\Resources\ContactRequests\Pages\ViewContactRequest;
use App\Models\ContactRequest;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactRequestResource extends Resource
{
    protected static ?string $model = ContactRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Demandes de contact';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Entreprise')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('intent')
                    ->label('Intention')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'warning',
                        'read' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Nouveau',
                        'read' => 'Lu',
                        'archived' => 'Archivé',
                        default => $state,
                    }),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'new' => 'Nouveau',
                        'read' => 'Lu',
                        'archived' => 'Archivé',
                    ]),

                SelectFilter::make('intent')
                    ->label('Intention')
                    ->options([
                        'Demande démo DMS' => 'Demande démo DMS',
                        'Implémentation ERP' => 'Implémentation ERP',
                        'Consultation Digitale' => 'Consultation Digitale',
                        'Gestion de Flotte' => 'Gestion de Flotte',
                        'Autre Enquête' => 'Autre Enquête',
                    ]),
            ])
            ->recordAction('view')
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactRequests::route('/'),
            'view' => ViewContactRequest::route('/{record}'),
        ];
    }
}
