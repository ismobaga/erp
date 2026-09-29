<?php

namespace Crommix\Blog\Filament\Resources\BlogPosts;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\User;
use BackedEnum;
use Crommix\Blog\Filament\Concerns\BlogResourceHelpers;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use Crommix\Blog\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use Crommix\Blog\Models\BlogPost;
use Crommix\Blog\Support\BlogContent;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    use BlogResourceHelpers;
    use HasPermissionAccess;

    protected static string $permissionScope = 'blog';

    protected static ?string $companyFeature = 'blog';

    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

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
                                ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set): void {
                                    if (blank($state)) {
                                        return;
                                    }

                                    // Only follow the title while the slug was never customised.
                                    if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                            static::companySlugInput('Utilisé dans l’URL publique : /blog/{slug}.'),
                            Textarea::make('excerpt')
                                ->label('Extrait')
                                ->rows(3)
                                ->maxLength(500)
                                ->placeholder('Résumé court visible dans la liste des articles...'),
                            static::richContentEditor('content', 'Corps de l’article'),
                        ]),
                    Grid::make(1)
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            Section::make('Publication')
                                ->extraAttributes(['class' => 'ledger-summary-card'])
                                ->schema([
                                    Select::make('status')
                                        ->disabled(fn (): bool => ! static::canPublish())
                                        ->label('Statut')
                                        ->options([
                                            'draft' => 'Brouillon',
                                            'published' => 'Publié',
                                        ])
                                        ->native(false)
                                        ->default('draft')
                                        ->required(),
                                    DateTimePicker::make('published_at')
                                        ->disabled(fn (): bool => ! static::canPublish())
                                        ->label('Date de publication')
                                        ->helperText('Laisser vide pour publier immédiatement ; une date future programme l’article.')
                                        ->seconds(false),
                                    Toggle::make('is_featured')
                                        ->disabled(fn (): bool => ! static::canPublish())
                                        ->label('Mettre à la une')
                                        ->helperText('L’article à la une le plus récent est mis en avant en tête du blog.'),
                                    Select::make('author_id')
                                        ->label('Auteur')
                                        ->options(fn (): array => static::authorOptions())
                                        ->default(fn (): ?int => auth()->id())
                                        ->searchable(),
                                ]),
                            Section::make('Classement')
                                ->extraAttributes(['class' => 'ledger-summary-card'])
                                ->schema([
                                    Select::make('category_id')
                                        ->label('Catégorie')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->label('Nom')->required()->maxLength(255),
                                        ]),
                                    Select::make('stage')
                                        ->label('Stade du projet')
                                        ->options(BlogPost::STAGES)
                                        ->native(false)
                                        ->placeholder('—')
                                        ->helperText('Pour les articles Labs : affiche un badge Expérimental / Bêta / Disponible.'),
                                    Select::make('tags')
                                        ->label('Mots-clés')
                                        ->relationship('tags', 'name')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->label('Nom')->required()->maxLength(100),
                                        ]),
                                ]),
                            Section::make('Image de couverture')
                                ->extraAttributes(['class' => 'ledger-summary-card'])
                                ->schema([
                                    FileUpload::make('cover_image_path')
                                        ->hiddenLabel()
                                        ->image()
                                        ->imageEditor()
                                        ->imageAspectRatio('16:9')
                                        ->maxSize(4096)
                                        ->disk(BlogContent::disk())
                                        ->directory('blog/covers')
                                        ->visibility('public'),
                                    TextInput::make('cover_image_alt')
                                        ->label('Texte alternatif')
                                        ->maxLength(255)
                                        ->helperText('Décrit l’image pour l’accessibilité et le SEO.'),
                                ]),
                            Section::make('SEO')
                                ->extraAttributes(['class' => 'ledger-summary-card'])
                                ->collapsible()
                                ->schema([
                                    TextInput::make('seo_title')
                                        ->label('Titre SEO')
                                        ->maxLength(70)
                                        ->helperText('Idéalement moins de 60 caractères.'),
                                    Textarea::make('seo_description')
                                        ->label('Description SEO')
                                        ->maxLength(320)
                                        ->rows(3)
                                        ->helperText('Idéalement 150 à 160 caractères. Par défaut : l’extrait.'),
                                    Placeholder::make('public_url_hint')
                                        ->label('Aperçu URL')
                                        ->content(fn (Get $get): string => '/blog/'.($get('slug') ?: '{slug}')),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image_path')
                    ->label('')
                    ->disk(BlogContent::disk())
                    ->visibility('public')
                    ->imageWidth(64)
                    ->imageHeight(36),
                TextColumn::make('title')->label('Article')->searchable()->sortable()->limit(60),
                TextColumn::make('category.name')->label('Catégorie')->badge()->color('info')->toggleable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (BlogPost $record): string => static::statusLabel($record))
                    ->color(fn (BlogPost $record): string => match (static::statusLabel($record)) {
                        'Publié' => 'success',
                        'Programmé' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('stage')
                    ->label('Stade')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => BlogPost::STAGES[$state] ?? null)
                    ->color(fn (?string $state): string => match ($state) {
                        'available' => 'success',
                        'beta' => 'info',
                        default => 'warning',
                    })
                    ->toggleable(),
                IconColumn::make('is_featured')->label('À la une')->boolean()->toggleable(),
                TextColumn::make('author.name')->label('Auteur')->toggleable(),
                TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('updated_at')->since()->label('Mis à jour')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
                    ]),
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
                SelectFilter::make('tags')
                    ->label('Mot-clé')
                    ->relationship('tags', 'name')
                    ->multiple(),
                TernaryFilter::make('is_featured')->label('À la une'),
            ])
            ->recordActions([
                Action::make('view_public')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (BlogPost $record): string => route('blog.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (BlogPost $record): bool => BlogPost::query()->published()->whereKey($record->getKey())->exists()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function statusLabel(BlogPost $record): string
    {
        if ($record->status !== 'published') {
            return 'Brouillon';
        }

        return $record->published_at !== null && $record->published_at->isFuture() ? 'Programmé' : 'Publié';
    }

    /** @return array<int, string> Users of the current company only. */
    protected static function authorOptions(): array
    {
        $company = currentCompany();

        $query = $company !== null ? $company->users() : User::query()->whereKey(auth()->id());

        return $query->orderBy('name')->pluck('name', 'users.id')->all();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canEdit(Model $record): bool
    {
        return static::canChangeRecord($record, 'update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canChangeRecord($record, 'delete');
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
