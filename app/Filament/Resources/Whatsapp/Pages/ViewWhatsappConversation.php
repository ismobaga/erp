<?php

namespace App\Filament\Resources\Whatsapp\Pages;

use App\Filament\Resources\Whatsapp\WhatsappConversationResource;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\GowaClient;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWhatsappConversation extends ViewRecord
{
    protected static string $resource = WhatsappConversationResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conversation')->schema([
                TextEntry::make('chat_id')->label('JID WhatsApp'),
                TextEntry::make('contact_name')->label('Nom du contact'),
                TextEntry::make('client.company_name')
                    ->label('Client')
                    ->formatStateUsing(fn(?string $state, WhatsappConversation $record): string => $state ?: ($record->client?->contact_name ?? '-')),
                TextEntry::make('status')->badge()->label('Statut'),
                TextEntry::make('assignedUser.name')->label('Assigné à'),
                TextEntry::make('last_message_at')->dateTime()->label('Dernier message'),
            ])->columns(2),
            Section::make('Messages')->schema([
                TextEntry::make('messages_summary')
                    ->label('')
                    ->columnSpanFull()
                    ->getStateUsing(function (WhatsappConversation $record): string {
                        $messages = $record->messages()->latest('sent_at')->limit(50)->get()->reverse();

                        if ($messages->isEmpty()) {
                            return 'Aucun message.';
                        }

                        return $messages->map(function (WhatsappMessage $msg): string {
                            $dir    = $msg->isInbound() ? '← ' : '→ ';
                            $time   = $msg->sent_at?->format('d/m/Y H:i') ?? '';
                            $body   = $msg->body ?? '[' . $msg->type . ']';
                            $status = $msg->isOutbound() ? ' [' . $msg->ack_status . ']' : '';

                            return sprintf('%s [%s] %s%s', $dir, $time, $body, $status);
                        })->implode("\n");
                    })
                    ->prose(),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Répondre')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data, GowaClient $gowa): void {
                    /** @var WhatsappConversation $conversation */
                    $conversation = $this->getRecord();
                    $company      = currentCompany();

                    if (!$company || blank($company->whatsapp_device_id)) {
                        Notification::make()->title('Device ID manquant')->danger()->send();

                        return;
                    }

                    // Strip @s.whatsapp.net / @g.us to get the phone/group id for GoWA
                    $phone = $conversation->chat_id;

                    try {
                        $response = $gowa->sendText(
                            phone: $phone,
                            message: $data['message'],
                            deviceId: $company->whatsapp_device_id,
                        );

                        WhatsappMessage::create([
                            'conversation_id' => $conversation->id,
                            'message_id'      => data_get($response, 'results.message_id'),
                            'direction'       => 'outbound',
                            'event_type'      => 'message',
                            'type'            => 'text',
                            'body'            => $data['message'],
                            'ack_status'      => 'server',
                            'sent_at'         => now(),
                        ]);

                        $conversation->update(['last_message_at' => now()]);

                        Notification::make()->title('Message envoyé')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec envoi')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('back')
                ->label('Retour')
                ->color('gray')
                ->url(WhatsappConversationResource::getUrl('index')),
        ];
    }
}
