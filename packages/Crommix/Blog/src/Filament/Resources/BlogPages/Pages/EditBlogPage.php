<?php

namespace Crommix\Blog\Filament\Resources\BlogPages\Pages;

use Crommix\Blog\Filament\Resources\BlogPages\BlogPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPage extends EditRecord
{
    protected static string $resource = BlogPageResource::class;

    public function getTitle(): string
    {
        return 'Modifier la page';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Supprimer'),
        ];
    }
}
