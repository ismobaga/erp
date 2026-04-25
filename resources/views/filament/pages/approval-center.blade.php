<x-filament-panels::page>
    <div class="space-y-8">
        <section class="grid gap-6 xl:grid-cols-12">
            <div
                class="xl:col-span-8 rounded-3xl bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] p-8 text-white shadow-[0_16px_40px_rgba(11,28,48,0.16)]">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#8df5e4]">Approbations managériales
                </p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.03em]">Examiner les allocations de ressources en
                    attente</h2>
                <p class="mt-2 max-w-2xl text-sm text-white/75">Des éléments d'approbation requièrent une attention
                    immédiate
                    sur le grand livre et le flux de remboursement.</p>

                <div class="mt-8 flex flex-wrap gap-10">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#8df5e4]">Volume total</p>
                        <p class="text-4xl font-black">{{ $summary['volume'] }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#8df5e4]">Éléments signalés
                        </p>
                        <p class="text-4xl font-black">{{ $summary['flagged'] }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6 xl:col-span-4">
                <div class="architectural-card">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Temps moyen
                        d'approbation</p>
                    <div class="mt-2 flex items-end justify-between">
                        <span class="text-4xl font-black text-[#0b1c30]">{{ $summary['avg_time'] }}</span>
                        <span
                            class="rounded-full bg-[#8df5e4] px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.18em] text-[#00201c]">Sain</span>
                    </div>
                </div>

                <div class="architectural-card">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Capacité mensuelle</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#dce9ff]">
                        <div class="h-full rounded-full bg-[#43af9f]" style="width: {{ $summary['capacity'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-[#57657a]">{{ $summary['capacity'] }}% de la capacité trimestrielle
                        d'approbation
                        atteinte.</p>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">File d'approbation</p>
                    <h3 class="text-2xl font-black tracking-[-0.03em] text-[#002045]">Nécessite une action</h3>
                </div>
                <span
                    class="rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#002045]">{{ count($items) }}
                    éléments actifs</span>
            </div>

            @foreach ($items as $item)
                <div class="architectural-card flex items-center justify-between gap-6 overflow-hidden"
                    style="border-left: 4px solid {{ $item['tone'] === 'danger' ? '#ba1a1a' : ($item['tone'] === 'success' ? '#43af9f' : '#002045') }};">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#eff4ff] text-[#002045]">
                            <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-[#002045]">{{ $item['subject'] }}</p>
                            <p class="text-xs text-[#57657a]">{{ $item['reference'] }} · {{ $item['category'] }}</p>
                            <p class="mt-1 text-xs text-[#74777f]">{{ $item['note'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <p
                            class="text-lg font-black {{ $item['tone'] === 'danger' ? 'text-[#ba1a1a]' : 'text-[#0b1c30]' }}">
                            {{ $item['amount'] }}
                        </p>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}"
                                class="rounded-xl bg-[#8df5e4] px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-[#00201c]">{{ $item['cta'] }}</a>
                        @else
                            <span
                                class="rounded-xl bg-[#8df5e4] px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-[#00201c]">{{ $item['cta'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 md:grid-cols-3">
            <div class="architectural-card bg-[#eff4ff]/65">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">En attente par service</p>
                <div class="mt-4 space-y-3">
                    @foreach ($departments as $department)
                        <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2">
                            <span class="text-sm font-medium text-[#0b1c30]">{{ $department['name'] }}</span>
                            <span
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-[#002045]">{{ $department['count'] }}
                                entrées</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="architectural-card bg-[#eff4ff]/65">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Volume mensuel</p>
                <div class="mt-4 flex h-28 items-end gap-2">
                    <span class="w-full rounded-t bg-[#d6e3ff]" style="height: 40%"></span>
                    <span class="w-full rounded-t bg-[#d6e3ff]" style="height: 60%"></span>
                    <span class="w-full rounded-t bg-[#d6e3ff]" style="height: 45%"></span>
                    <span class="w-full rounded-t bg-[#d6e3ff]" style="height: 80%"></span>
                    <span class="w-full rounded-t bg-[#43af9f]" style="height: 95%"></span>
                    <span class="w-full rounded-t bg-[#d6e3ff]" style="height: 30%"></span>
                </div>
            </div>

            <div class="architectural-card bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] text-white">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Protocole d'audit</p>
                <h4 class="mt-2 text-xl font-black">Les transactions au-delà des seuils majeurs exigent une vérification
                    secondaire.
                </h4>
                <p class="mt-2 text-sm text-white/70">La surcouche de conformité Crommix reste active pour les
                    opérations financières.
                </p>
            </div>
        </section>
    </div>
</x-filament-panels::page>