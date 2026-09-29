{{-- Labs maturity badge. Expects $post. --}}
@if($post->stageLabel())
    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] {{ match ($post->stage) {
        'available' => 'bg-[#2d6a4f]/15 text-[#1b4332]',
        'beta' => 'bg-[#dce9ff] text-[#2d476f]',
        default => 'bg-[#d4a574]/25 text-[#7a5020]',
    } }}">{{ $post->stageLabel() }}</span>
@endif
