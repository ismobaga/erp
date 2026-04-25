<?php

namespace Crommix\Blog\Filament\Resources\BlogPages\Pages;

use Crommix\Blog\Filament\Resources\BlogPages\BlogPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPages extends ListRecords
{
    protected static string $resource = BlogPageResource::class;

    public function getTitle(): string
    {
        return 'Pages publiques';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle page'),
        ];
    }
}
