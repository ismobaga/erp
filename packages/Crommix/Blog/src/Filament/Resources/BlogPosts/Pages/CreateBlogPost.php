<?php

namespace Crommix\Blog\Filament\Resources\BlogPosts\Pages;

use Crommix\Blog\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = $data['author_id'] ?? auth()->id();

        return $data;
    }
}
