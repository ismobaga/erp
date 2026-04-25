<?php

namespace Crommix\Blog\Filament\Resources\BlogPages;

use BackedEnum;
use Crommix\Blog\Filament\Resources\BlogPages\Pages\CreateBlogPage;
use Crommix\Blog\Filament\Resources\BlogPages\Pages\EditBlogPage;
use Crommix\Blog\Filament\Resources\BlogPages\Pages\ListBlogPages;
use Crommix\Blog\Models\BlogPage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPageResource extends Resource
{
    protected static ?string $model = BlogPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    Section::make('Page')
                        ->columnSpan(['lg' => 8])
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    if (blank($state)) {
                                        return;
                                    }

                                    $set('slug', Str::slug($state));
                                }),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            TextInput::make('hero_title')->maxLength(255),
                            Textarea::make('hero_subtitle')->rows(2),
                            Textarea::make('content')
                                ->rows(18)
                                ->required(),
                        ]),
                    Section::make('Publication')
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required(),
                            DateTimePicker::make('published_at')
                                ->seconds(false),
                            Select::make('template')
                                ->options([
                                    'default' => 'Default',
                                    'landing' => 'Landing',
                                ])
                                ->default('default')
                                ->required(),
                            TextInput::make('seo_title')->maxLength(255),
                            Textarea::make('seo_description')->rows(3),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('template')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
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
            'index' => ListBlogPages::route('/'),
            'create' => CreateBlogPage::route('/create'),
            'edit' => EditBlogPage::route('/{record}/edit'),
        ];
    }
}
