{{-- Labs maturity badge. Expects $post. --}}
@if($post->stageLabel())
    <span class="badge {{ match ($post->stage) {
        'available' => 'badge-available',
        'beta' => 'badge-muted',
        default => 'badge-soon',
    } }}">{{ $post->stageLabel() }}</span>
@endif
