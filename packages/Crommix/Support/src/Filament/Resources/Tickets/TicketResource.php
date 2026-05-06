<?php

namespace Crommix\Support\Filament\Resources\Tickets;

use App\Models\User;
use BackedEnum;
use Crommix\Support\Filament\Resources\Tickets\Pages\CreateTicket;
use Crommix\Support\Filament\Resources\Tickets\Pages\EditTicket;
use Crommix\Support\Filament\Resources\Tickets\Pages\ListTickets;
use Crommix\Support\Models\Ticket;
use Crommix\Support\Models\TicketCategory;
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

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('subject')
                ->label('Subject')
                ->required()
                ->maxLength(255),
            Select::make('category_id')
                ->label('Category')
                ->options(fn(): array => TicketCategory::active()->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            Select::make('priority')
                ->label('Priority')
                ->options([
                    'low'    => 'Low',
                    'medium' => 'Medium',
                    'high'   => 'High',
                    'urgent' => 'Urgent',
                ])
                ->native(false)
                ->default('medium')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'open'        => 'Open',
                    'in_progress' => 'In Progress',
                    'resolved'    => 'Resolved',
                    'closed'      => 'Closed',
                ])
                ->native(false)
                ->default('open')
                ->required(),
            TextInput::make('requester_name')
                ->label('Requester Name')
                ->maxLength(255),
            TextInput::make('requester_email')
                ->label('Requester Email')
                ->email()
                ->maxLength(255),
            Select::make('assigned_to')
                ->label('Assigned To')
                ->options(fn(): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            Textarea::make('description')
                ->label('Description')
                ->rows(5)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')->label('Subject')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('priority')->label('Priority')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high'   => 'warning',
                        'medium' => 'info',
                        'low'    => 'gray',
                        default  => 'gray',
                    }),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open'        => 'info',
                        'in_progress' => 'warning',
                        'resolved'    => 'success',
                        'closed'      => 'gray',
                        default       => 'gray',
                    }),
                TextColumn::make('requester_name')->label('Requester')->searchable()->toggleable(),
                TextColumn::make('assignee.name')->label('Assigned To')->toggleable(),
                TextColumn::make('created_at')->label('Created')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open'        => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved'    => 'Resolved',
                        'closed'      => 'Closed',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                        'urgent' => 'Urgent',
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
            'index'  => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit'   => EditTicket::route('/{record}/edit'),
        ];
    }
}
