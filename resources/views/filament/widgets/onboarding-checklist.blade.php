<x-filament-widgets::widget>
    @php
        $steps = $this->getSteps();
        $completedCount = collect($steps)->where('done', true)->count();
        $totalCount = count($steps);
    @endphp

    @if(!empty($steps) && $completedCount < $totalCount)
    <div style="background: linear-gradient(135deg, #eff4ff 0%, #fff 100%); border-radius: 16px; padding: 28px 32px; border: 1px solid #dce9ff;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 16px; font-weight: 900; color: #002045; margin-bottom: 4px;">
                    🚀 Démarrage rapide
                </h2>
                <p style="font-size: 13px; color: #57657a;">
                    {{ $completedCount }}/{{ $totalCount }} étapes complétées — vous y êtes presque !
                </p>
            </div>
            <div style="background: #dce9ff; border-radius: 999px; padding: 4px 4px; width: 160px; height: 10px; overflow: hidden;">
                <div style="background: #002045; border-radius: 999px; height: 100%; width: {{ round(($completedCount / max($totalCount, 1)) * 100) }}%; transition: width 0.3s;"></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            @foreach($steps as $step)
            <div style="background: {{ $step['done'] ? '#f0fdf4' : '#ffffff' }}; border-radius: 12px; padding: 16px; border: 1px solid {{ $step['done'] ? '#bbf7d0' : '#e5eaf2' }};">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="font-size: 18px;">{{ $step['done'] ? '✅' : '⭕' }}</span>
                    <span style="font-size: 13px; font-weight: 800; color: {{ $step['done'] ? '#166534' : '#002045' }};">{{ $step['label'] }}</span>
                </div>
                <p style="font-size: 12px; color: #57657a; margin-bottom: 12px; line-height: 1.5;">{{ $step['description'] }}</p>
                @if(!$step['done'])
                <a href="{{ $step['url'] }}"
                   style="display: inline-block; background: #002045; color: #fff; text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.05em;">
                    {{ $step['cta'] }}
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</x-filament-widgets::widget>
