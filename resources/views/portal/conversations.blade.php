<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.conversations.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
        .conv-card {
            background: #fff;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e5eaf2;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,32,69,0.05);
        }
        .conv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8faff;
            border-bottom: 1px solid #e5eaf2;
        }
        .conv-title { font-size: 14px; font-weight: 800; color: #002045; }
        .conv-last-msg { font-size: 12px; color: #57657a; margin-top: 2px; }
        .conv-messages { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @if($conversations->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon">💬</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.conversations.empty') }}</p>
                <p>{{ __('erp.portal.conversations.empty_hint') }}</p>
            </div>
        </div>
    @else
        <div class="card-title" style="padding:0 0 16px;">{{ __('erp.portal.conversations.title') }}</div>

        @foreach($conversations as $conversation)
            <div class="conv-card">
                <div class="conv-header">
                    <div>
                        <div class="conv-title">{{ $conversation->displayName() }}</div>
                        @if($conversation->last_message_at)
                            <div class="conv-last-msg">
                                {{ __('erp.portal.conversations.last_message') }} : {{ $conversation->last_message_at->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </div>
                    <span class="badge badge-{{ $conversation->status === 'open' ? 'open' : 'closed' }}">
                        {{ $conversation->status }}
                    </span>
                </div>

                @if($conversation->messages->isNotEmpty())
                    <div class="conv-messages">
                        @foreach($conversation->messages->sortBy('sent_at') as $message)
                            <div style="display:flex;flex-direction:column;align-items:{{ $message->isInbound() ? 'flex-start' : 'flex-end' }};">
                                <div class="bubble bubble-{{ $message->direction }}">
                                    @if($message->body)
                                        {{ $message->body }}
                                    @elseif($message->type === 'image')
                                        🖼 {{ app()->getLocale() === 'fr' ? '[Image]' : '[Image]' }}
                                    @elseif($message->type === 'audio')
                                        🎵 {{ app()->getLocale() === 'fr' ? '[Audio]' : '[Audio]' }}
                                    @elseif($message->type === 'document')
                                        📎 {{ app()->getLocale() === 'fr' ? '[Document]' : '[Document]' }}
                                    @else
                                        {{ app()->getLocale() === 'fr' ? '[Message]' : '[Message]' }}
                                    @endif
                                    <div class="bubble-meta">
                                        {{ $message->isInbound() ? __('erp.portal.conversations.you') : __('erp.portal.conversations.agent') }}
                                        · {{ $message->sent_at?->format('d/m/Y H:i') ?? '—' }}
                                        @if($message->isOutbound() && $message->ack_status)
                                            · {{ $message->ack_status }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @endif

</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
