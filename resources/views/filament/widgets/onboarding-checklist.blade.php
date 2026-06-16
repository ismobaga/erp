<x-filament-widgets::widget>
    @php
        $steps = $this->getSteps();
        $completedCount = collect($steps)->where('done', true)->count();
        $totalCount = count($steps);
        $pct = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
    @endphp

    @if(!empty($steps) && $completedCount < $totalCount)
    <div class="architectural-card border border-[#dce9ff]">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Démarrage rapide</p>
                <h3 class="mt-1 text-lg font-black tracking-[-0.02em] text-[#002045]">Configurez votre espace</h3>
                <p class="mt-0.5 text-xs text-[#57657a]">{{ $completedCount }}/{{ $totalCount }} étapes complétées</p>
            </div>

            <div class="flex flex-col items-end gap-1">
                <span class="text-sm font-black text-[#002045]">{{ $pct }}%</span>
                <div class="h-2 w-36 overflow-hidden rounded-full bg-[#dce9ff]">
                    <div class="h-full rounded-full bg-[#002045] transition-all duration-500"
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        {{-- Steps --}}
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($steps as $step)
                <div class="relative flex flex-col rounded-2xl p-5 transition
                    {{ $step['done']
                        ? 'bg-[#f0fdf4] ring-1 ring-[#bbf7d0]'
                        : 'bg-white ring-1 ring-[#e5eaf2] hover:ring-[#002045]/20' }}">

                    <div class="mb-3 flex items-center gap-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm
                            {{ $step['done'] ? 'bg-[#dcfce7] text-[#166534]' : 'bg-[#eff4ff] text-[#002045]' }}">
                            {{ $step['done'] ? '✓' : '○' }}
                        </span>
                        <span class="text-xs font-black {{ $step['done'] ? 'text-[#166534]' : 'text-[#002045]' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>

                    <p class="flex-1 text-[11px] leading-relaxed text-[#57657a]">{{ $step['description'] }}</p>

                    @if(!$step['done'])
                        <a href="{{ $step['url'] }}"
                           class="mt-4 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-[0.14em] text-[#002045] transition hover:opacity-70">
                            {{ $step['cta'] }} →
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif
</x-filament-widgets::widget>
