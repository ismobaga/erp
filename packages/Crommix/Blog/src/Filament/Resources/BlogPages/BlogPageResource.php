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

class BlogPageResource extends Resource
{
    protected static ?string $model = BlogPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Pages publiques';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    Section::make('Structure de page')
                        ->description('Créez des pages vitrines, landing produits ou contenus institutionnels.')
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
                                ->helperText('Utilisé dans l’URL publique de la page.'),
                            TextInput::make('hero_title')
                                ->label('Titre hero')
                                ->maxLength(255),
                            Textarea::make('hero_subtitle')
                                ->label('Sous-titre hero')
                                ->rows(2),
                            Textarea::make('content')
                                ->label('Contenu')
                                ->rows(18)
                                ->placeholder('Décrivez votre offre, vos bénéfices et appels à action...')
                                ->required(),
                        ]),
                    Section::make('Publication et SEO')
                        ->description('Pilotez la visibilité web et les métadonnées.')
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
                            Select::make('template')
                                ->label('Template')
                                ->options([
                                    'default' => 'Standard',
                                    'landing' => 'Landing produit',
                                ])
                                ->native(false)
                                ->default('default')
                                ->required(),
                            TextInput::make('seo_title')->label('Titre SEO')->maxLength(255),
                            Textarea::make('seo_description')->label('Description SEO')->rows(3),
                            Placeholder::make('public_url_hint')
                                ->label('Aperçu URL')
                                ->content('/pages/{slug}'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Page')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('template')
                    ->label('Template')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === 'landing' ? 'Landing produit' : 'Standard'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === 'published' ? 'Publié' : 'Brouillon')
                    ->color(fn(string $state): string => $state === 'published' ? 'success' : 'gray'),
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
            'index' => ListBlogPages::route('/'),
            'create' => CreateBlogPage::route('/create'),
            'edit' => EditBlogPage::route('/{record}/edit'),
        ];
    }
}
