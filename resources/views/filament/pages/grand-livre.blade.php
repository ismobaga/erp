<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ── PAGE HEADER ──────────────────────────────────────────────────── --}}
        <section class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Comptabilité générale</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[#002045]">Grand Livre</h2>
                <p class="mt-1 text-sm font-medium text-[#57657a]">
                    Vue consolidée des écritures comptables et des soldes par type de compte.
                </p>
            </div>

            {{-- Filters --}}
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="rounded-2xl border border-[#c4c6cf]/40 bg-white px-4 py-3 shadow-sm">
                    <label for="gl-period"
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-[#1A365D]">Période</label>
                    <select id="gl-period" wire:model.live="period"
                        class="mt-2 w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-3 py-2 text-xs font-bold text-[#1A365D] outline-none transition focus:border-[#1A365D] focus:ring-2 focus:ring-[#1A365D]/10">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rounded-2xl border border-[#c4c6cf]/40 bg-white px-4 py-3 shadow-sm">
                    <label for="gl-status"
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-[#1A365D]">Statut</label>
                    <select id="gl-status" wire:model.live="statusFilter"
                        class="mt-2 w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-3 py-2 text-xs font-bold text-[#1A365D] outline-none transition focus:border-[#1A365D] focus:ring-2 focus:ring-[#1A365D]/10">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        {{-- ── KPI CARDS ────────────────────────────────────────────────────── --}}
        <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                <article class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">
                            {{ $kpi['label'] }}
                        </span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl"
                            style="background: {{ $kpi['bg'] }}; color: {{ $kpi['color'] }}">
                            <x-dynamic-component :component="$kpi['icon']" class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-2xl font-black" style="color: {{ $kpi['color'] }}">{{ $kpi['value'] }}</p>
                    <p class="mt-2 text-[10px] font-medium text-[#74777f]">{{ $kpi['note'] }}</p>
                </article>
            @endforeach
        </section>

        {{-- ── ACCOUNT BALANCE SUMMARY ─────────────────────────────────────── --}}
        @if (!empty($accountSummary))
            <section class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Bilan comptable</p>
                        <h3 class="mt-1 text-lg font-black tracking-[-0.02em] text-[#002045]">
                            Soldes par type de compte
                        </h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Écritures validées uniquement.</p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-[#002045]">
                        Postées
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#c4c6cf]/20 text-left">
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">Type
                                    de compte</th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Total débit</th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Total crédit</th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Solde net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c4c6cf]/10">
                            @foreach ($accountSummary as $row)
                                <tr class="group transition hover:bg-[#f8faff]">
                                    <td class="py-4">
                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em]"
                                            style="background: {{ $row['bg'] }}; color: {{ $row['fg'] }}">
                                            {{ $row['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right text-[12px] font-bold text-[#0b1c30]">
                                        {{ $row['total_debit'] }}</td>
                                    <td class="py-4 text-right text-[12px] font-bold text-[#0b1c30]">
                                        {{ $row['total_credit'] }}</td>
                                    <td class="py-4 text-right text-[12px] font-black"
                                        style="color: {{ $row['net_positive'] ? '#005048' : '#ba1a1a' }}">
                                        {{ $row['net_balance'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ── JOURNAL ENTRIES TABLE ────────────────────────────────────────── --}}
        <section class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Journal des opérations
                    </p>
                    <h3 class="mt-1 text-lg font-black tracking-[-0.02em] text-[#002045]">
                        Écritures comptables
                    </h3>
                    <p class="text-[11px] font-medium text-[#57657a]">50 dernières écritures sur la période active.</p>
                </div>
                <span
                    class="inline-flex items-center rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-[#002045]">
                    {{ count($entries) }} écriture(s)
                </span>
            </div>

            @if (empty($entries))
                <div
                    class="rounded-2xl border border-dashed border-[#c4c6cf] bg-[#f8faff] px-6 py-10 text-center text-sm text-[#57657a]">
                    Aucune écriture comptable sur cette période.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#c4c6cf]/20 text-left">
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    N° écriture</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">Date
                                </th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Description</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Source</th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Débit</th>
                                <th
                                    class="pb-4 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Crédit</th>
                                <th
                                    class="pb-4 text-center text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Statut</th>
                                <th
                                    class="pb-4 text-center text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                    Équilibre</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c4c6cf]/10">
                            @foreach ($entries as $entry)
                                <tr class="group transition hover:bg-[#f8faff]">
                                    {{-- N° écriture --}}
                                    <td class="py-4">
                                        <span
                                            class="font-mono text-[11px] font-black text-[#002045]">{{ $entry['entry_number'] }}</span>
                                    </td>

                                    {{-- Date --}}
                                    <td class="py-4 text-[11px] font-medium text-[#57657a]">{{ $entry['entry_date'] }}
                                    </td>

                                    {{-- Description --}}
                                    <td class="max-w-[280px] py-4">
                                        <p class="truncate text-[12px] font-medium text-[#0b1c30]"
                                            title="{{ $entry['description'] }}">
                                            {{ $entry['description'] }}
                                        </p>
                                        @if ($entry['creator'] !== '—')
                                            <p class="mt-0.5 text-[10px] text-[#74777f]">par {{ $entry['creator'] }}</p>
                                        @endif
                                    </td>

                                    {{-- Source --}}
                                    <td class="py-4">
                                        <span
                                            class="rounded-md bg-[#eff4ff] px-2 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-[#002045]">
                                            {{ $entry['source_label'] }}
                                        </span>
                                    </td>

                                    {{-- Débit --}}
                                    <td class="py-4 text-right text-[12px] font-black text-[#005048]">
                                        {{ $entry['total_debit'] }}</td>

                                    {{-- Crédit --}}
                                    <td class="py-4 text-right text-[12px] font-black text-[#7c2d12]">
                                        {{ $entry['total_credit'] }}</td>

                                    {{-- Statut --}}
                                    <td class="py-4 text-center">
                                        @php
                                            $statusStyle = match ($entry['status']) {
                                                'posted' => 'bg-[#dff7f0] text-[#005048]',
                                                'voided' => 'bg-[#fde8d8] text-[#7c2d12]',
                                                default  => 'bg-[#f3f4f6] text-[#57657a]',
                                            };
                                        @endphp
                                        <span
                                            class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.14em] {{ $statusStyle }}">
                                            {{ $entry['status_label'] }}
                                        </span>
                                    </td>

                                    {{-- Équilibre --}}
                                    <td class="py-4 text-center">
                                        @if ($entry['balanced'])
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#dff7f0]">
                                                <svg class="h-3.5 w-3.5 text-[#005048]" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#fde8d8]">
                                                <svg class="h-3.5 w-3.5 text-[#ba1a1a]" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>
</x-filament-panels::page>
