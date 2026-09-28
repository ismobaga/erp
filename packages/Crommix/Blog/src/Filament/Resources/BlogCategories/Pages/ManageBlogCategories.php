<?php

namespace Crommix\Blog\Filament\Resources\BlogCategories\Pages;

use Crommix\Blog\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlogCategories extends ManageRecords
{
    protected static string $resource = BlogCategoryResource::class;

    public function getTitle(): string
    {
        return 'Catégories du blog';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle catégorie'),
        ];
    }
}
