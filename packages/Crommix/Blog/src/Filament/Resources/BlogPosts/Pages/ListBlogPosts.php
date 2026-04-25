<?php

namespace Crommix\Blog\Filament\Resources\BlogPosts\Pages;

use Crommix\Blog\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;

    public function getTitle(): string
    {
        return 'Articles du blog';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvel article'),
        ];
    }
}
