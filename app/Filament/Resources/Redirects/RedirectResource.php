<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Unique;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedirectResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'redirects';

    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Redirections';

    protected static ?string $modelLabel = 'redirection';

    protected static ?string $pluralModelLabel = 'redirections';

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'source_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('source_path')
                ->label('Chemin court')
                ->prefix(rtrim(url('/'), '/'))
                ->placeholder('/ai')
                ->required()
                ->maxLength(255)
                ->regex('#^/?[A-Za-z0-9\-._~/]+$#')
                ->dehydrateStateUsing(fn (?string $state): string => Redirect::normalizePath((string) $state))
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('company_id', currentCompany()?->getKey()),
                )
                ->rule(static fn (): Closure => static::pathIsFree(...))
                ->validationMessages([
                    'regex' => 'Utilisez uniquement lettres, chiffres, tirets, points et « / ».',
                    'unique' => 'Une redirection existe déjà pour ce chemin.',
                ])
                ->helperText('Ex. /ai — les majuscules et le « / » final sont ignorés.'),
            TextInput::make('target_url')
                ->label('Destination')
                ->placeholder('https://ai.crommixmali.com')
                ->required()
                ->url()
                ->maxLength(2048)
                ->rule(static fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                    static::guardAgainstLoop((string) $value, (string) $get('source_path'), (bool) $get('include_subpaths'), $fail);
                }),
            Select::make('status_code')
                ->label('Type')
                ->options(Redirect::STATUS_CODES)
                ->default(302)
                ->native(false)
                ->required()
                ->helperText('Commencez en 302. Passez en 301 quand la destination est stable : les navigateurs mémorisent une 301 durablement.'),
            Toggle::make('include_subpaths')
                ->label('Inclure les sous-chemins')
                ->helperText('/ai/docs → destination/docs'),
            Toggle::make('preserve_query')
                ->label('Conserver les paramètres (?ref=…)')
                ->default(true),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('source_path')
            ->columns([
                TextColumn::make('source_path')
                    ->label('Chemin')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->description(fn (Redirect $record): ?string => $record->include_subpaths ? 'avec sous-chemins' : null),
                TextColumn::make('target_url')
                    ->label('Destination')
                    ->searchable()
                    ->limit(50)
                    ->url(fn (Redirect $record): string => $record->target_url, shouldOpenInNewTab: true),
                TextColumn::make('status_code')
                    ->label('Type')
                    ->badge()
                    ->color(fn (int $state): string => $state === 301 ? 'success' : 'warning'),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (Redirect $record): bool => ! static::canEdit($record)),
                TextColumn::make('hits')->label('Visites')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Dernière visite')->since()->placeholder('—')->sortable(),
            ])
            ->recordActions([
                Action::make('test')
                    ->label('Tester')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Redirect $record): string => url($record->source_path))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /** A redirect for a path an existing page already answers would never fire. */
    public static function pathIsFree(string $attribute, mixed $value, Closure $fail): void
    {
        $path = Redirect::normalizePath((string) $value);

        if ($path === '/') {
            $fail('La page d’accueil ne peut pas être redirigée.');

            return;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpException) {
            return;
        }

        if (! $route->isFallback) {
            $fail('Ce chemin est déjà utilisé par une page du site.');
        }
    }

    /** Reject a destination on this same site that would redirect back to itself. */
    public static function guardAgainstLoop(string $target, string $source, bool $includeSubpaths, Closure $fail): void
    {
        $host = parse_url($target, PHP_URL_HOST);

        if (! is_string($host) || strcasecmp($host, (string) request()->getHost()) !== 0) {
            return;
        }

        $source = Redirect::normalizePath($source);
        $targetPath = Redirect::normalizePath((string) (parse_url($target, PHP_URL_PATH) ?? '/'));

        if ($targetPath === $source || ($includeSubpaths && str_starts_with($targetPath, $source.'/'))) {
            $fail('Cette destination renverrait vers la redirection elle-même (boucle).');
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedirects::route('/'),
        ];
    }
}
