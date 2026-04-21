<x-filament-widgets::widget>
    <div class="space-y-6">

        {{-- ── HERO HEADER BAR ──────────────────────────────────────────────── --}}
        <div
            class="flex flex-col gap-4 rounded-2xl bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] px-6 py-4 text-white shadow-[0_12px_32px_rgba(11,28,48,0.18)] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-white/10 p-2 text-[#8df5e4]">
                    <x-filament::icon icon="heroicon-o-document-chart-bar" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Comptabilité générale</p>
                    <p class="text-sm font-black text-white">Journal des écritures — vue analytique</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Balance indicator --}}
                @if ($balanced)
                    <span
                        class="flex items-center gap-1.5 rounded-full bg-[#8df5e4]/20 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#8df5e4]">
                        <span class="h-2 w-2 rounded-full bg-[#8df5e4]"></span>
                        Grand livre équilibré
                    </span>
                @else
                    <span
                        class="flex items-center gap-1.5 rounded-full bg-[#ba1a1a]/30 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#ffb4ab]">
                        <span class="h-2 w-2 rounded-full bg-[#ffb4ab]"></span>
                        Déséquilibre détecté
                    </span>
                @endif
                <span
                    class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white">
                    {{ $total }} écriture(s)
                </span>
            </div>
        </div>

        {{-- ── KPI GRID ─────────────────────────────────────────────────────── --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total posted --}}
            <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">Validées</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#dff7f0] text-[#005048]">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    </div>
                </div>
                <p class="text-2xl font-black text-[#005048]">{{ $posted }}</p>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#f3f4f6]">
                    <div class="h-full rounded-full bg-[#43af9f]" style="width: {{ $postedPct }}%"></div>
                </div>
                <p class="mt-1.5 text-[10px] font-medium text-[#74777f]">{{ $postedPct }}% du total</p>
            </div>

            {{-- Draft --}}
            <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">Brouillons</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#eff4ff] text-[#002045]">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                    </div>
                </div>
                <p class="text-2xl font-black text-[#002045]">{{ $draft }}</p>
                <p class="mt-3 text-[10px] font-medium text-[#74777f]">En attente de validation</p>
            </div>

            {{-- Voided --}}
            <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">Annulées</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#fde8d8] text-[#7c2d12]">
                        <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4" />
                    </div>
                </div>
                <p class="text-2xl font-black text-[#7c2d12]">{{ $voided }}</p>
                <p class="mt-3 text-[10px] font-medium text-[#74777f]">Écritures invalidées</p>
            </div>

            {{-- Total debit / credit --}}
            <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">Mouvements</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#d6e3ff] text-[#002045]">
                        <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-[#57657a]">Débit</span>
                        <span class="text-[11px] font-black text-[#005048]">{{ $totalDebit }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-[#57657a]">Crédit</span>
                        <span class="text-[11px] font-black text-[#7c2d12]">{{ $totalCredit }}</span>
                    </div>
                </div>
                <p class="mt-3 text-[10px] font-medium text-[#74777f]">Écritures validées uniquement</p>
            </div>
        </div>

        {{-- ── TREND CHART + SOURCE BREAKDOWN ──────────────────────────────── --}}
        <div class="grid gap-6 xl:grid-cols-12">

            {{-- Monthly trend bar chart --}}
            <div class="xl:col-span-8 rounded-3xl border border-[#c4c6cf]/20 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-black text-[#002045]">Tendance mensuelle</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Écritures validées vs brouillons sur 7 mois</p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">
                        <span class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#002045]"></span>Validées
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#b9c7df]"></span>Brouillons
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto pb-2">
                    <div
                        class="relative flex h-40 min-w-[400px] items-end justify-between gap-3 border-b border-l border-[#c4c6cf]/20 px-2 pt-6">
                        <div class="pointer-events-none absolute inset-x-0 bottom-1/4 h-px bg-[#c4c6cf]/10"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-2/4 h-px bg-[#c4c6cf]/10"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-3/4 h-px bg-[#c4c6cf]/10"></div>

                        @foreach ($trend as $month)
                            <div class="flex min-w-12 flex-1 shrink-0 flex-col items-center gap-1.5">
                                <div class="flex w-full items-end justify-center gap-1">
                                    <span
                                        class="w-4 rounded-t {{ $month['active'] ? 'bg-[#002045]' : 'bg-[#002045]/30' }}"
                                        style="height: {{ max(2, $month['height_posted']) }}px"></span>
                                    <span class="w-4 rounded-t bg-[#b9c7df]"
                                        style="height: {{ max(2, $month['height_draft']) }}px"></span>
                                </div>
                                <span
                                    class="text-center text-[9px] font-black uppercase leading-tight {{ $month['active'] ? 'text-[#002045]' : 'text-[#74777f]' }}">
                                    {{ $month['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Source type breakdown --}}
            <div class="xl:col-span-4 rounded-3xl border border-[#c4c6cf]/20 bg-[#f8faff] p-6 shadow-sm">
                <h3 class="text-base font-black text-[#002045]">Origine des écritures</h3>
                <p class="mb-5 text-[11px] font-medium text-[#57657a]">Répartition par type de source</p>

                @if (empty($sourceBreakdown))
                    <p class="text-sm text-[#57657a]">Aucune donnée disponible.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($sourceBreakdown as $source)
                            <div>
                                <div class="mb-1.5 flex items-end justify-between gap-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.14em]"
                                        style="background: {{ $source['bg'] }}; color: {{ $source['fg'] }}">
                                        {{ $source['label'] }}
                                    </span>
                                    <div class="text-right">
                                        <span class="text-[10px] font-black text-[#002045]">{{ $source['count'] }}</span>
                                        <span class="ml-1 text-[9px] font-bold text-[#74777f]">({{ $source['pct'] }}%)</span>
                                    </div>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full"
                                        style="width: {{ $source['pct'] }}%; background: {{ $source['fg'] }}; opacity: 0.7">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
