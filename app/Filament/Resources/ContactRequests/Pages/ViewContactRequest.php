<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewContactRequest extends ViewRecord
{
    protected static string $resource = ContactRequestResource::class;

    protected string $view = 'filament.resources.contact-requests.pages.view-contact-request';

    public function getTitle(): string|Htmlable
    {
        return 'Demande de ' . $this->record->name;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === 'new') {
            $this->record->markAsRead();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('archive')
                ->label('Archiver')
                ->color('gray')
                ->icon('heroicon-o-archive-box')
                ->visible(fn () => $this->record->status !== 'archived')
                ->action(function () {
                    $this->record->update(['status' => 'archived']);
                    $this->refreshFormData(['status']);
                }),

            Action::make('reopen')
                ->label('Ré-ouvrir')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->status === 'archived')
                ->action(function () {
                    $this->record->update(['status' => 'read']);
                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make(),
        ];
    }
}
