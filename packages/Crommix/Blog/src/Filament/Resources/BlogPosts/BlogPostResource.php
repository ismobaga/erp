<?php

namespace Crommix\Blog\Filament\Resources\BlogPosts;

use App\Models\User;
use BackedEnum;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use Crommix\Blog\Models\BlogPost;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    Section::make('Contenu éditorial')
                        ->description('Rédigez un article orienté acquisition et savoir-faire client.')
                        ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                        ->columnSpan(['lg' => 8])
                        ->schema([
                            TextInput::make('title')
                                ->label('Titre')
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
                                ->label('Slug URL')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Utilisé dans l’URL publique du blog.'),
                            Textarea::make('excerpt')
                                ->label('Extrait')
                                ->rows(3)
                                ->placeholder('Résumé court visible dans la liste des articles...'),
                            Textarea::make('content')
                                ->label('Corps de l’article')
                                ->rows(18)
                                ->placeholder('Rédigez le contenu principal de votre article...')
                                ->required(),
                        ]),
                    Section::make('Publication et SEO')
                        ->description('Contrôlez le statut, l’auteur et le référencement naturel.')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            Select::make('status')
                                ->label('Statut')
                                ->options([
                                    'draft' => 'Brouillon',
                                    'published' => 'Publié',
                                ])
                                ->native(false)
                                ->default('draft')
                                ->required(),
                            DateTimePicker::make('published_at')
                                ->label('Date de publication')
                                ->seconds(false),
                            Select::make('author_id')
                                ->label('Auteur')
                                ->options(fn(): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload(),
                            TextInput::make('seo_title')
                                ->label('Titre SEO')
                                ->maxLength(255),
                            Textarea::make('seo_description')
                                ->label('Description SEO')
                                ->rows(3),
                            Placeholder::make('public_url_hint')
                                ->label('Aperçu URL')
                                ->content('/blog/{slug}'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Article')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === 'published' ? 'Publié' : 'Brouillon')
                    ->color(fn(string $state): string => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('author.name')->label('Auteur')->toggleable(),
                TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('updated_at')->since()->label('Mis à jour'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
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
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
