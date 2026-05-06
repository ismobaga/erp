<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.tickets.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
        .ticket-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 14px;
            border: 1px solid #e5eaf2;
            box-shadow: 0 1px 4px rgba(0,32,69,0.05);
        }
        .ticket-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }
        .ticket-subject { font-size: 15px; font-weight: 800; color: #002045; }
        .ticket-meta { font-size: 12px; color: #57657a; margin-top: 4px; }
        .ticket-body { font-size: 14px; color: #374151; line-height: 1.6; margin-top: 8px; white-space: pre-line; }
        .ticket-reply {
            margin-top: 14px;
            background: #f8faff;
            border-radius: 8px;
            padding: 12px 16px;
            border-left: 3px solid #002045;
        }
        .ticket-reply-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #57657a; margin-bottom: 6px; }
        .ticket-reply-text { font-size: 14px; color: #374151; line-height: 1.6; white-space: pre-line; }
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @if(session('portal_success'))
        <div class="flash-success">✓ {{ session('portal_success') }}</div>
    @endif

    {{-- New ticket form --}}
    <div class="card">
        <div class="card-title">{{ __('erp.portal.tickets.new') }}</div>
        <form method="POST" action="{{ route('portal.tickets.submit', ['token' => $token]) }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="subject">{{ __('erp.portal.tickets.subject') }}</label>
                <input id="subject" name="subject" type="text" class="form-control"
                       value="{{ old('subject') }}" required maxlength="255"
                       placeholder="{{ app()->getLocale() === 'fr' ? 'Objet de votre demande' : 'Subject of your request' }}">
                @error('subject')
                    <p style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="body">{{ __('erp.portal.tickets.body') }}</label>
                <textarea id="body" name="body" class="form-control" rows="5" required maxlength="5000"
                          placeholder="{{ app()->getLocale() === 'fr' ? 'Décrivez votre demande en détail…' : 'Describe your request in detail…' }}">{{ old('body') }}</textarea>
                @error('body')
                    <p style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="priority">{{ __('erp.portal.tickets.priority') }}</label>
                <select id="priority" name="priority" class="form-control" style="max-width:240px;">
                    <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>{{ __('erp.portal.tickets.priority_normal') }}</option>
                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>{{ __('erp.portal.tickets.priority_urgent') }}</option>
                </select>
            </div>
            <button type="submit" class="btn">{{ __('erp.portal.tickets.submit') }}</button>
        </form>
    </div>

    {{-- Ticket history --}}
    @if($tickets->isNotEmpty())
        <div class="card-title" style="padding:8px 0 14px;">{{ __('erp.portal.tickets.title') }}</div>

        @foreach($tickets as $ticket)
            <div class="ticket-card">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-subject">{{ $ticket->subject }}</div>
                        <div class="ticket-meta">
                            {{ __('erp.portal.tickets.created_at') }} {{ $ticket->created_at?->format('d/m/Y H:i') ?? '—' }}
                            @if($ticket->priority === 'urgent')
                                · <span style="color:#dc2626;font-weight:700;">⚡ {{ __('erp.portal.tickets.priority_urgent') }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="badge badge-{{ $ticket->status }}">
                        {{ __('erp.portal.tickets.statuses.' . $ticket->status) ?: $ticket->status }}
                    </span>
                </div>
                <div class="ticket-body">{{ $ticket->body }}</div>
                @if($ticket->reply)
                    <div class="ticket-reply">
                        <div class="ticket-reply-label">{{ __('erp.portal.tickets.reply') }}</div>
                        <div class="ticket-reply-text">{{ $ticket->reply }}</div>
                        @if($ticket->replied_at)
                            <div style="font-size:11px;color:#57657a;margin-top:6px;">{{ $ticket->replied_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="empty" style="padding:30px 20px;">
                <div class="empty-icon">🎫</div>
                <p style="font-size:14px;font-weight:600;margin-bottom:4px;">{{ __('erp.portal.tickets.empty') }}</p>
                <p style="font-size:13px;">{{ __('erp.portal.tickets.empty_hint') }}</p>
            </div>
        </div>
    @endif

</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
