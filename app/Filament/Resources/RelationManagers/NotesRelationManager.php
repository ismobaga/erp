<?php

namespace App\Filament\Resources\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'noteRecords';

    protected static ?string $title = 'Notes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('noted_at')
                    ->label('Date de la note')
                    ->default(now())
                    ->required(),
                Select::make('user_id')
                    ->label('Auteur')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('body')
                    ->label('Note')
                    ->rows(5)
                    ->maxLength(4000)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('noted_at', 'desc')
            ->columns([
                TextColumn::make('noted_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Auteur')
                    ->searchable(),
                TextColumn::make('body')
                    ->label('Contenu')
                    ->limit(120)
                    ->wrap()
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] ??= auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
