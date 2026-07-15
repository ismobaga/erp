<?php

namespace Crommix\POS\Filament\Resources\PosOrders\Pages;

use Crommix\POS\Filament\Resources\PosOrders\PosOrderResource;
use Crommix\POS\Models\PosSession;
use Crommix\POS\Services\PosService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePosOrder extends CreateRecord
{
    protected static string $resource = PosOrderResource::class;

    /**
     * Sales go through PosService::processSale so that stock is deducted and
     * the ledger entry is posted atomically — never through a plain create.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $session = PosSession::query()->open()->latest('opened_at')->first();

        if ($session === null) {
            Notification::make()
                ->title('No open till session.')
                ->body('Open a till session before recording a sale.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'items' => 'No open till session — open one from the Till Sessions page first.',
            ]);
        }

        $items = collect($data['items'] ?? [])
            ->map(fn (array $item): array => [
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'] ?? '',
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
            ])
            ->all();

        unset($data['items']);

        return app(PosService::class)->processSale($session, $data, $items);
    }
}
