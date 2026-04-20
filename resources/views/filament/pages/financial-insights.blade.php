<x-filament-panels::page>
    <div class="space-y-8">
        <section class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Centre de pilotage
                    financier
                </p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[#002045]">Analyses financières</h2>
                <p class="mt-1 text-sm font-medium text-[#57657a]">Vue détaillée des performances financières sur la
                    période sélectionnée.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="rounded-2xl border border-[#c4c6cf]/40 bg-white px-4 py-3 shadow-sm">
                    <label for="analytics-period"
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-[#1A365D]">Période</label>
                    <select id="analytics-period" wire:model.live="period"
                        class="mt-2 w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-3 py-2 text-xs font-bold text-[#1A365D] outline-none transition focus:border-[#1A365D] focus:ring-2 focus:ring-[#1A365D]/10">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-[11px] font-bold text-[#1A365D]">{{ $periodLabel }}</p>
                    <p class="text-[10px] font-medium text-[#57657a]" wire:loading.delay>Mise à jour des indicateurs…
                    </p>
                </div>
                <div class="rounded-2xl bg-[#1A365D] px-5 py-3 text-white shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#8df5e4]">Analyse IA</p>
                    <p class="max-w-xs text-xs font-medium">{{ $insight }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                <article class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <span
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">{{ $kpi['label'] }}</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eff4ff] text-[#1A365D]">
                            <x-dynamic-component :component="$kpi['icon']" class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <span class="text-2xl font-black text-[#1A365D]">{{ $kpi['value'] }}</span>
                        <span
                            class="text-[11px] font-black {{ $kpi['trendTone'] === 'negative' ? 'text-[#ba1a1a]' : ($kpi['trendTone'] === 'warning' ? 'text-[#a16207]' : 'text-[#005048]') }}">{{ $kpi['trend'] }}</span>
                    </div>
                    <p class="mt-2 text-[10px] font-medium text-[#74777f]">{{ $kpi['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8 rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#1A365D]">Revenus et dépenses</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Comparaison mensuelle sur la période active
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">
                        <span class="flex items-center gap-2"><span
                                class="h-2.5 w-2.5 rounded-full bg-[#1A365D]"></span>Revenus</span>
                        <span class="flex items-center gap-2"><span
                                class="h-2.5 w-2.5 rounded-full bg-[#b9c7df]"></span>Dépenses</span>
                    </div>
                </div>

                @php($chartMinWidth = max(640, count($monthly) * 64))
                <div class="overflow-x-auto pb-2">
                    <div class="relative flex h-64 items-end justify-between gap-3 border-b border-l border-[#c4c6cf]/20 px-2 pt-10"
                        style="min-width: {{ $chartMinWidth }}px;">
                        <div class="pointer-events-none absolute inset-x-0 bottom-1/4 h-px bg-[#c4c6cf]/10"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-2/4 h-px bg-[#c4c6cf]/10"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-3/4 h-px bg-[#c4c6cf]/10"></div>

                        @foreach ($monthly as $month)
                            <div class="flex min-w-12 flex-1 shrink-0 flex-col items-center gap-2">
                                <div class="flex w-full items-end justify-center gap-1.5">
                                    <span class="w-5 rounded-t {{ $month['active'] ? 'bg-[#1A365D]' : 'bg-[#1A365D]/30' }}"
                                        style="height: {{ $month['revenueHeight'] }}px"></span>
                                    <span class="w-5 rounded-t bg-[#b9c7df]"
                                        style="height: {{ $month['expenseHeight'] }}px"></span>
                                </div>
                                <span
                                    class="text-center text-[9px] font-black uppercase leading-tight {{ $month['active'] ? 'text-[#1A365D]' : 'text-[#74777f]' }}">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <h3 class="text-lg font-black text-[#1A365D]">Répartition du chiffre d’affaires</h3>
                <p class="mb-8 text-[11px] font-medium text-[#57657a]">Analyse par catégorie de service</p>

                <div
                    class="mx-auto flex h-40 w-40 items-center justify-center rounded-full border-8 border-[#1A365D] text-center">
                    <div>
                        <p class="text-xl font-black text-[#1A365D]">{{ $kpis['revenue']['value'] }}</p>
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-[#74777f]">Total</p>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4">
                    @foreach ($breakdown as $slice)
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $slice['color'] }}"></span>
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-[0.12em] text-[#74777f]">
                                    {{ $slice['label'] }}
                                </p>
                                <p class="text-[11px] font-black text-[#1A365D]">{{ $slice['share'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-5 rounded-3xl border border-[#c4c6cf]/20 bg-[#f8faff] p-8 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#1A365D]">Efficacité de recouvrement</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Analyse de l’ancienneté des créances</p>
                    </div>
                    <span class="rounded-xl bg-[#1A365D] px-3 py-2 text-xs font-black text-white">DSO</span>
                </div>

                <div class="space-y-5">
                    @foreach ($aging as $bucket)
                        <div>
                            <div class="mb-2 flex items-end justify-between gap-3">
                                <span
                                    class="text-[10px] font-black uppercase tracking-[0.12em] text-[#57657a]">{{ $bucket['label'] }}</span>
                                <span class="text-xs font-black text-[#1A365D]">{{ $bucket['value'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full {{ $bucket['tone'] }}"
                                    style="width: {{ $bucket['width'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="xl:col-span-7 rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#1A365D]">Transactions récentes importantes</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Mouvements à forte valeur sur les
                            encaissements et les dépenses</p>
                    </div>
                    <span
                        class="rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#1A365D]">Temps
                        réel</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#c4c6cf]/20 text-left">
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">Date
                                </th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Entité</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">Type
                                </th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Montant</th>
                                <th
                                    class="pb-4 text-center text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c4c6cf]/10">
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td class="py-4 text-[11px] font-medium text-[#57657a]">{{ $transaction['date'] }}</td>
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#eff4ff] text-[10px] font-black text-[#1A365D]">
                                                {{ $transaction['initials'] }}
                                            </div>
                                            <span
                                                class="text-[12px] font-bold text-[#1A365D]">{{ $transaction['entity'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-[11px] font-medium text-[#0b1c30]">{{ $transaction['type'] }}</td>
                                    <td class="py-4 text-right text-[12px] font-black text-[#1A365D]">
                                        {{ $transaction['amount_label'] }}
                                    </td>
                                    <td class="py-4 text-center">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase {{ $transaction['badge'] === 'success' ? 'bg-[#dff7f0] text-[#005048]' : 'bg-[#d5e3fc] text-[#3a485b]' }}">{{ $transaction['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>