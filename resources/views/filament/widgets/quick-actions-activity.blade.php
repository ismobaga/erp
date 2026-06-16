<x-filament-widgets::widget>
    <div class="grid gap-5 xl:grid-cols-2">

        {{-- Quick Actions --}}
        <div class="architectural-card">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e] mb-4">Actions rapides</p>
            <div class="flex flex-wrap gap-2">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-[#eff4ff] px-4 py-2.5 text-xs font-black text-[#002045] transition hover:bg-[#002045] hover:text-white">
                        @if(!empty($action['icon']))
                            <x-filament::icon :icon="$action['icon']" class="h-3.5 w-3.5" />
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="architectural-card">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e] mb-4">Activité récente</p>
            @if(empty($activities))
                <p class="text-sm text-[#57657a]">Aucune activité récente.</p>
            @else
                <ul class="space-y-2">
                    @foreach($activities as $activity)
                        <li class="flex items-start gap-3 rounded-xl bg-[#eff4ff]/50 px-3 py-2.5">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#002045]"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-[#0b1c30]">{{ $activity['label'] }}</p>
                                <p class="mt-0.5 text-[10px] text-[#57657a]">{{ $activity['time'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</x-filament-widgets::widget>
