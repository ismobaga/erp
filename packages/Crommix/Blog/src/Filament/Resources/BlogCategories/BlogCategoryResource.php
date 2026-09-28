<?php

namespace Crommix\Blog\Filament\Resources\BlogCategories;

use App\Filament\Concerns\HasPermissionAccess;
use BackedEnum;
use Crommix\Blog\Filament\Concerns\BlogResourceHelpers;
use Crommix\Blog\Filament\Resources\BlogCategories\Pages\ManageBlogCategories;
use Crommix\Blog\Models\BlogCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogCategoryResource extends Resource
{
    use BlogResourceHelpers;
    use HasPermissionAccess;

    protected static string $permissionScope = 'blog';

    protected static ?string $companyFeature = 'blog';

    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Catégories';

    protected static ?string $modelLabel = 'catégorie';

    protected static ?string $pluralModelLabel = 'catégories';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set): void {
                    if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            static::companySlugInput('Utilisé dans l’URL : /blog/categorie/{slug}.'),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->helperText('Affichée en tête de la page de la catégorie.')
                ->columnSpanFull(),
            TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->minValue(0)
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('posts_count')->counts('posts')->label('Articles')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlogCategories::route('/'),
        ];
    }
}
