<?php

namespace Crommix\SaaS\Filament\Resources\TenantPlans;

use BackedEnum;
use Crommix\SaaS\Filament\Resources\TenantPlans\Pages\CreateTenantPlan;
use Crommix\SaaS\Filament\Resources\TenantPlans\Pages\EditTenantPlan;
use Crommix\SaaS\Filament\Resources\TenantPlans\Pages\ListTenantPlans;
use Crommix\SaaS\Models\TenantPlan;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TenantPlanResource extends Resource
{
    protected static ?string $model = TenantPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('saas.plans.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $featureOptions = array_map(
            static fn(string $label, string $key): string => $label,
            config('saas.features', []),
            array_keys(config('saas.features', [])),
        );

        return $schema->components([
            TextInput::make('name')
                ->label('Plan Name')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('price_monthly')
                ->label('Monthly Price')
                ->numeric()
                ->prefix('FCFA')
                ->default(0),
            TextInput::make('price_yearly')
                ->label('Yearly Price')
                ->numeric()
                ->prefix('FCFA')
                ->default(0),
            TextInput::make('trial_days')
                ->label('Trial Days')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Select::make('features')
                ->label('Included Features')
                ->multiple()
                ->options(config('saas.features', []))
                ->columnSpanFull(),
            KeyValue::make('limits')
                ->label('Quota Limits')
                ->keyLabel('Metric')
                ->valueLabel('Limit (leave blank for unlimited)')
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Toggle::make('is_public')
                ->label('Publicly Visible')
                ->default(true),
            TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),
                TextColumn::make('price_monthly')
                    ->label('Monthly')
                    ->money('FCFA')
                    ->sortable(),
                TextColumn::make('trial_days')
                    ->label('Trial Days')
                    ->sortable(),
                TextColumn::make('subscriptions_count')
                    ->label('Tenants')
                    ->counts('subscriptions')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTenantPlans::route('/'),
            'create' => CreateTenantPlan::route('/create'),
            'edit'   => EditTenantPlan::route('/{record}/edit'),
        ];
    }
}
