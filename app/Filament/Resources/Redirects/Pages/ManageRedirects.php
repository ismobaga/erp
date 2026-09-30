<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRedirects extends ManageRecords
{
    protected static string $resource = RedirectResource::class;

    public function getTitle(): string
    {
        return 'Redirections';
    }

    public function getSubheading(): ?string
    {
        return 'Liens courts du site public, ex. '.url('/ai').' → https://ai.crommixmali.com';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle redirection'),
        ];
    }
}
