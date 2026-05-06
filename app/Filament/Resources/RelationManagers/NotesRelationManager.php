<?php

namespace App\Filament\Resources\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'noteRecords';

    protected static ?string $title = 'Notes internes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations')
                    ->description('Renseignez le contexte et l\'auteur de la note.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('noted_at')
                                    ->label('Date de la note')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(),
                                Select::make('user_id')
                                    ->label('Auteur')
                                    ->relationship('author', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(auth()->id())
                                    ->required(),
                            ]),
                    ]),
                Section::make('Contenu de la note')
                    ->schema([
                        Textarea::make('body')
                            ->label('Note')
                            ->rows(7)
                            ->maxLength(4000)
                            ->placeholder('Ex: échange client du jour, décision prise, prochaine action...')
                            ->required()
                            ->columnSpanFull(),
                        Placeholder::make('body_hint')
                            ->label('Conseil')
                            ->content('Privilégiez une note concise avec date, faits observés et action à suivre.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('noted_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('noted_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('author.name')
                    ->label('Auteur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Contenu')
                    ->limit(160)
                    ->tooltip(fn($record): ?string => filled($record->body) ? (string) $record->body : null)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Saisie par')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Auteur')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('noted_period')
                    ->label('Période')
                    ->form([
                        DatePicker::make('from')
                            ->label('Du')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Au')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn(Builder $query): Builder => $query->whereDate('noted_at', '>=', $data['from'])
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn(Builder $query): Builder => $query->whereDate('noted_at', '<=', $data['until'])
                            );
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nouvelle note')
                    ->icon('heroicon-o-document-text')
                    ->modalHeading('Ajouter une note interne')
                    ->modalSubmitActionLabel('Enregistrer la note')
                    ->mutateDataUsing(function (array $data): array {
                        $userId = auth()->id();

                        $data['created_by'] = $userId;
                        $data['user_id'] ??= $userId;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Modifier la note'),
                DeleteAction::make()
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Supprimer la sélection')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ]);
    }
}
