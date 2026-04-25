<?php

namespace Crommix\Blog\Filament\Resources\BlogPages\Pages;

use Crommix\Blog\Filament\Resources\BlogPages\BlogPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPage extends CreateRecord
{
    protected static string $resource = BlogPageResource::class;
}
