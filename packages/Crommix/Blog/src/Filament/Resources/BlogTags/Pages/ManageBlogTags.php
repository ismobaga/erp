<?php

namespace Crommix\Blog\Filament\Resources\BlogTags\Pages;

use Crommix\Blog\Filament\Resources\BlogTags\BlogTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlogTags extends ManageRecords
{
    protected static string $resource = BlogTagResource::class;

    public function getTitle(): string
    {
        return 'Mots-clés du blog';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouveau mot-clé'),
        ];
    }
}
