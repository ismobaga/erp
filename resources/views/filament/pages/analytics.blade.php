<x-filament-panels::page>
    <div class="space-y-8">
        {{-- ── Header ──────────────────────────────────────────────────────────── --}}
        <section class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">
                    Vue opérationnelle globale
                </p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[#002045]">
                    Tableau de bord analytique
                </h2>
                <p class="mt-1 text-sm font-medium text-[#57657a]">
                    Indicateurs consolidés — Finance, Clients, Projets, CRM et RH.
                </p>
            </div>

            <div class="rounded-2xl border border-[#c4c6cf]/40 bg-white px-4 py-3 shadow-sm">
                <label for="analytics-global-period"
                    class="text-[10px] font-black uppercase tracking-[0.18em] text-[#1A365D]">
                    Période
                </label>
                <select id="analytics-global-period" wire:model.live="period"
                    class="mt-2 w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-3 py-2 text-xs font-bold text-[#1A365D] outline-none transition focus:border-[#1A365D] focus:ring-2 focus:ring-[#1A365D]/10">
                    @foreach ($periodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $period)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-[11px] font-bold text-[#1A365D]">{{ $periodLabel }}</p>
            </div>
        </section>

        {{-- ── Finance KPIs ─────────────────────────────────────────────────────── --}}
        @if (!empty($finance))
            <section>
                <h3 class="mb-4 text-[10px] font-black uppercase tracking-[0.2em] text-[#57657a]">
                    Finance
                </h3>
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @php
                        $financeCards = [
                            [
                                'label' => 'Chiffre d\'affaires',
                                'value' => number_format($finance['revenue'] ?? 0, 0, ',', ' ') . ' FCFA',
                                'icon'  => 'heroicon-o-banknotes',
                                'tone'  => 'blue',
                            ],
                            [
                                'label' => 'Encaissements',
                                'value' => number_format($finance['collected'] ?? 0, 0, ',', ' ') . ' FCFA',
                                'icon'  => 'heroicon-o-arrow-trending-up',
                                'tone'  => 'green',
                            ],
                            [
                                'label' => 'Dépenses',
                                'value' => number_format($finance['expenses'] ?? 0, 0, ',', ' ') . ' FCFA',
                                'icon'  => 'heroicon-o-credit-card',
                                'tone'  => 'red',
                            ],
                            [
                                'label' => 'Taux de recouvrement',
                                'value' => ($finance['collection_rate'] ?? 0) . ' %',
                                'icon'  => 'heroicon-o-chart-bar-square',
                                'tone'  => ($finance['collection_rate'] ?? 0) >= 75 ? 'green' : 'yellow',
                            ],
                        ];
                    @endphp
                    @foreach ($financeCards as $card)
                        @php
                            $toneMap = [
                                'blue'   => 'bg-blue-50 text-blue-700',
                                'green'  => 'bg-emerald-50 text-emerald-700',
                                'red'    => 'bg-rose-50 text-rose-700',
                                'yellow' => 'bg-amber-50 text-amber-700',
                            ];
                        @endphp
                        <article class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-6 shadow-sm">
                            <div class="mb-3 flex items-start justify-between gap-4">
                                <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">
                                    {{ $card['label'] }}
                                </span>
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $toneMap[$card['tone']] ?? 'bg-slate-50 text-slate-600' }}">
                                    <x-dynamic-component :component="$card['icon']" class="h-5 w-5" />
                                </div>
                            </div>
                            <p class="text-2xl font-black text-[#1A365D]">{{ $card['value'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Clients & Projects ───────────────────────────────────────────────── --}}
        <section class="grid gap-6 xl:grid-cols-2">
            {{-- Clients --}}
            @if (!empty($clients))
                <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 text-base font-black text-[#1A365D]">
                        Clients
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        @foreach ([
                            ['label' => 'Total', 'value' => $clients['total'] ?? 0],
                            ['label' => 'Actifs', 'value' => $clients['active'] ?? 0],
                            ['label' => 'Nouveaux', 'value' => $clients['new'] ?? 0],
                        ] as $item)
                            <div class="rounded-2xl bg-[#f8faff] p-4 text-center">
                                <p class="text-2xl font-black text-[#002045]">{{ $item['value'] }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-[#57657a]">
                                    {{ $item['label'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Projects --}}
            @if (!empty($projects))
                <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 text-base font-black text-[#1A365D]">
                        Projets
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        @foreach ([
                            ['label' => 'Total', 'value' => $projects['total'] ?? 0],
                            ['label' => 'Nouveaux', 'value' => $projects['new'] ?? 0],
                            ['label' => 'Taux de complétion', 'value' => ($projects['completion_rate'] ?? 0) . ' %'],
                        ] as $item)
                            <div class="rounded-2xl bg-[#f8faff] p-4 text-center">
                                <p class="text-2xl font-black text-[#002045]">{{ $item['value'] }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-[#57657a]">
                                    {{ $item['label'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                    @if (!empty($projects['by_status']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($projects['by_status'] as $status => $count)
                                <span class="rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-bold text-[#1A365D]">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}: {{ $count }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </section>

        {{-- ── CRM & HR ─────────────────────────────────────────────────────────── --}}
        <section class="grid gap-6 xl:grid-cols-2">
            {{-- CRM --}}
            <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-black text-[#1A365D]">CRM — Leads</h3>
                    @if (empty($crm['available']))
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-500">
                            Module non activé
                        </span>
                    @endif
                </div>

                @if (!empty($crm['available']))
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ([
                            ['label' => 'Total', 'value' => $crm['total'] ?? 0],
                            ['label' => 'Nouveaux', 'value' => $crm['new'] ?? 0],
                            ['label' => 'Convertis', 'value' => $crm['converted'] ?? 0],
                            ['label' => 'Taux', 'value' => ($crm['conversion_rate'] ?? 0) . ' %'],
                        ] as $item)
                            <div class="rounded-2xl bg-[#f8faff] p-4 text-center">
                                <p class="text-xl font-black text-[#002045]">{{ $item['value'] }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-[#57657a]">
                                    {{ $item['label'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[#57657a]">
                        Le module CRM n'est pas encore activé pour cet environnement.
                    </p>
                @endif
            </div>

            {{-- HR --}}
            <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-black text-[#1A365D]">RH — Effectifs</h3>
                    @if (empty($hr['available']))
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-500">
                            Module non activé
                        </span>
                    @endif
                </div>

                @if (!empty($hr['available']))
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ([
                            ['label' => 'Total', 'value' => $hr['total'] ?? 0],
                            ['label' => 'Actifs', 'value' => $hr['active'] ?? 0],
                            ['label' => 'Recrutements', 'value' => $hr['hired'] ?? 0],
                            ['label' => 'Congés', 'value' => $hr['leave_requests'] ?? 0],
                        ] as $item)
                            <div class="rounded-2xl bg-[#f8faff] p-4 text-center">
                                <p class="text-xl font-black text-[#002045]">{{ $item['value'] }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-[#57657a]">
                                    {{ $item['label'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[#57657a]">
                        Le module RH n'est pas encore activé pour cet environnement.
                    </p>
                @endif
            </div>
        </section>
    </div>
</x-filament-panels::page>
