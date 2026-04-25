<x-filament-panels::page>
    <div class="space-y-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Sauvegarde</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Sauvegarde et restauration</h3>
                <p class="mt-3 text-sm text-slate-600">{{ $summary['latest_backup_label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['latest_backup_note'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Surveillance</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Alertes système</h3>
                <p class="mt-3 text-3xl font-black text-amber-700">{{ $summary['open_alerts'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Alertes actives sur 24h</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">File</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Jobs échoués</h3>
                <p class="mt-3 text-3xl font-black text-rose-700">{{ $summary['failed_jobs'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Jobs en attente: {{ $summary['queued_jobs'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Audit</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Audit administrateur</h3>
                <p class="mt-3 text-3xl font-black text-emerald-700">{{ $summary['audit_events_24h'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Événements sur les dernières 24h</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="text-lg font-black text-slate-900">Historique des sauvegardes</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($backups as $item)
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-sm font-bold text-slate-800">{{ $item['label'] }}</p>
                            <p class="truncate text-xs text-slate-500" title="{{ $item['path'] }}">{{ $item['path'] }}</p>
                            <div class="mt-1 flex items-center justify-between">
                                <p class="text-[10px] uppercase tracking-widest text-slate-400">{{ $item['time'] }}</p>
                                @if ($item['downloadUrl'] ?? null)
                                    <a href="{{ $item['downloadUrl'] }}"
                                        class="text-xs font-semibold text-[#002045] hover:underline dark:text-[#8df5e4]">
                                        Télécharger
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune archive récente.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="text-lg font-black text-slate-900">Alertes système</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($alerts as $item)
                        <div class="rounded-xl bg-rose-50 p-3">
                            <p class="text-sm font-bold text-rose-800">{{ $item['label'] }}</p>
                            <p class="text-xs text-rose-600">{{ $item['details'] }}</p>
                            <p class="text-[10px] uppercase tracking-widest text-rose-400">{{ $item['time'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune alerte critique récente.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="text-lg font-black text-slate-900">Audit administrateur</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($audits as $item)
                        <div class="rounded-xl bg-emerald-50 p-3">
                            <p class="text-sm font-bold text-emerald-800">{{ $item['label'] }}</p>
                            <p class="text-xs text-emerald-700">{{ $item['subject'] }}</p>
                            <p class="text-[10px] uppercase tracking-widest text-emerald-500">{{ $item['time'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune trace d’audit récente.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>