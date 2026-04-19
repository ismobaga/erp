<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\StaffDirectoryStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Staff & Collaborators';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StaffDirectoryStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add staff member'),
        ];
    }
}
