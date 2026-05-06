<?php

namespace Crommix\SaaS\Filament\Resources\TenantSubscriptions;

use App\Models\Company;
use BackedEnum;
use Crommix\SaaS\Filament\Resources\TenantSubscriptions\Pages\CreateTenantSubscription;
use Crommix\SaaS\Filament\Resources\TenantSubscriptions\Pages\EditTenantSubscription;
use Crommix\SaaS\Filament\Resources\TenantSubscriptions\Pages\ListTenantSubscriptions;
use Crommix\SaaS\Models\TenantPlan;
use Crommix\SaaS\Models\TenantSubscription;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantSubscriptionResource extends Resource
{
    protected static ?string $model = TenantSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('saas.subscriptions.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->label('Tenant')
                ->relationship('company', 'name')
                ->searchable()
                ->required(),
            Select::make('plan_id')
                ->label('Plan')
                ->relationship('plan', 'name')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'active'    => 'Active',
                    'trialing'  => 'Trialing',
                    'past_due'  => 'Past Due',
                    'cancelled' => 'Cancelled',
                    'expired'   => 'Expired',
                ])
                ->required(),
            DateTimePicker::make('trial_ends_at')
                ->label('Trial Ends At'),
            DateTimePicker::make('current_period_start')
                ->label('Period Start'),
            DateTimePicker::make('current_period_end')
                ->label('Period End'),
            TextInput::make('external_reference')
                ->label('External Reference')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(static fn(string $state): string => match ($state) {
                        'active'    => 'success',
                        'trialing'  => 'info',
                        'past_due'  => 'warning',
                        'cancelled' => 'danger',
                        'expired'   => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->label('Period End')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'trialing'  => 'Trialing',
                        'past_due'  => 'Past Due',
                        'cancelled' => 'Cancelled',
                        'expired'   => 'Expired',
                    ]),
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTenantSubscriptions::route('/'),
            'create' => CreateTenantSubscription::route('/create'),
            'edit'   => EditTenantSubscription::route('/{record}/edit'),
        ];
    }
}
