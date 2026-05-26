<x-filament-widgets::widget>
    <div style="display:grid; gap:16px;">
        <div style="background:#fff; border:1px solid #e5eaf2; border-radius:12px; padding:16px;">
            <h3 style="font-size:14px; font-weight:800; margin:0 0 10px; color:#002045;">Actions rapides</h3>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}"
                       style="display:inline-block; background:#002045; color:#fff; text-decoration:none; border-radius:8px; padding:8px 12px; font-size:12px; font-weight:700;">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div style="background:#fff; border:1px solid #e5eaf2; border-radius:12px; padding:16px;">
            <h3 style="font-size:14px; font-weight:800; margin:0 0 10px; color:#002045;">Activité récente</h3>
            @if(empty($activities))
                <p style="margin:0; font-size:12px; color:#57657a;">Aucune activité récente.</p>
            @else
                <ul style="margin:0; padding-left:16px; display:grid; gap:6px;">
                    @foreach($activities as $activity)
                        <li style="font-size:12px; color:#253143;">
                            {{ $activity['label'] }}
                            <span style="color:#6b7788;">· {{ $activity['time'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
