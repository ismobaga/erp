<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ── PAGE HEADER ─────────────────────────────────────────────────────── --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Exports analytiques</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[#002045]">Génération de rapports</h2>
                <p class="mt-1 max-w-2xl text-sm font-medium text-[#57657a]">
                    Configurez et générez vos rapports financiers approfondis. Choisissez les modules de données, la période et le format d'exportation pour vos audits et analyses stratégiques.
                </p>
            </div>
        </section>

        <form wire:submit="generateReport" class="space-y-6">
            <div class="grid gap-6 xl:grid-cols-12">

                {{-- ── Période d'analyse ────────────────────────────────────────── --}}
                <div class="relative xl:col-span-7 overflow-hidden rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 rounded-l-[1.25rem] bg-[#002045]"></span>
                    <div class="mb-6 flex items-center justify-between pl-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Configuration</p>
                            <h3 class="mt-1 text-lg font-black text-[#002045]">Période d'analyse</h3>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#57657a]">Timeframe</span>
                    </div>

                    <div class="grid gap-6 pl-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Du</label>
                            <input type="date" wire:model="startDate"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Au</label>
                            <input type="date" wire:model="endDate"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 pl-4">
                        <button type="button" wire:click="usePreset('quarter')"
                            class="rounded-lg bg-[#eff4ff] px-4 py-2 text-xs font-bold text-[#002045] transition hover:bg-[#002045] hover:text-white">
                            Dernier trimestre
                        </button>
                        <button type="button" wire:click="usePreset('year')"
                            class="rounded-lg bg-[#eff4ff] px-4 py-2 text-xs font-bold text-[#002045] transition hover:bg-[#002045] hover:text-white">
                            Année fiscale
                        </button>
                    </div>
                </div>

                {{-- ── Format d'exportation ─────────────────────────────────────── --}}
                <div class="relative xl:col-span-5 overflow-hidden rounded-[1.25rem] border border-[#c4c6cf]/30 bg-[#f8faff] p-8 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 rounded-l-[1.25rem] bg-[#8df5e4]"></span>
                    <div class="mb-6 pl-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Sortie</p>
                        <h3 class="mt-1 text-lg font-black text-[#002045]">Format d'exportation</h3>
                    </div>

                    <div class="space-y-3 pl-4">
                        @foreach ([
                            ['value' => 'pdf',   'label' => 'PDF Document',  'sub' => 'Rapport visuel (.pdf)',       'bg' => 'bg-[#fde8d8]', 'fg' => 'text-[#ba1a1a]'],
                            ['value' => 'csv',   'label' => 'Données CSV',   'sub' => 'Analyse brute (.csv)',        'bg' => 'bg-[#dff7f0]', 'fg' => 'text-[#005048]'],
                            ['value' => 'excel', 'label' => 'Excel',         'sub' => 'Classeur analytique (.xls)', 'bg' => 'bg-[#d6e3ff]', 'fg' => 'text-[#002045]'],
                        ] as $fmt)
                            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-transparent bg-white p-4 transition hover:border-[#c4c6cf]/30 hover:shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-black {{ $fmt['bg'] }} {{ $fmt['fg'] }}">
                                        {{ strtoupper(substr($fmt['value'], 0, 3)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-[#002045]">{{ $fmt['label'] }}</p>
                                        <p class="text-[10px] text-[#57657a]">{{ $fmt['sub'] }}</p>
                                    </div>
                                </div>
                                <input type="radio" wire:model="exportFormat" value="{{ $fmt['value'] }}"
                                    class="h-4 w-4 text-[#002045] focus:ring-[#002045]" />
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- ── Filtres de données ───────────────────────────────────────── --}}
                <div class="relative xl:col-span-8 overflow-hidden rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 rounded-l-[1.25rem] bg-[#43af9f]"></span>
                    <div class="mb-6 pl-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Périmètre</p>
                        <h3 class="mt-1 text-lg font-black text-[#002045]">Filtres de données</h3>
                    </div>

                    <div class="flex flex-wrap gap-3 pl-4">
                        @foreach ([
                            ['key' => 'revenue',    'icon' => '↗', 'label' => 'Chiffre d\'affaires'],
                            ['key' => 'expenses',   'icon' => '↘', 'label' => 'Dépenses'],
                            ['key' => 'payments',   'icon' => '💳', 'label' => 'Paiements'],
                            ['key' => 'taxes',      'icon' => '🏦', 'label' => 'Taxes & TVA'],
                            ['key' => 'audit',      'icon' => '🛡',  'label' => 'Audit & conformité'],
                            ['key' => 'whatsapp',   'icon' => '💬', 'label' => 'Analytics WhatsApp'],
                            ['key' => 'engagement', 'icon' => '👥', 'label' => 'Engagement client'],
                        ] as $module)
                            <label class="relative flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="selectedModules.{{ $module['key'] }}" class="peer sr-only" />
                                <div class="flex items-center gap-2 rounded-full bg-[#f8faff] px-5 py-2.5 text-sm font-semibold text-[#002045] transition peer-checked:bg-[#002045] peer-checked:text-white">
                                    <span>{{ $module['icon'] }}</span>
                                    {{ $module['label'] }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-5 pl-4 text-xs text-[#57657a]">
                        Le format CSV inclut un journal comptable structuré et une piste d'audit exploitable par les cabinets comptables.
                    </p>
                </div>

                {{-- ── Options avancées ─────────────────────────────────────────── --}}
                <div class="relative xl:col-span-4 flex flex-col justify-center overflow-hidden rounded-[1.25rem] bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] p-8 text-white shadow-sm">
                    <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-white/5"></div>
                    <div class="absolute -bottom-12 -left-12 h-24 w-24 rounded-full bg-white/5"></div>
                    <div class="relative">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Configuration</p>
                        <h3 class="mt-1 text-lg font-black">Options avancées</h3>
                    </div>

                    <label class="relative mt-6 flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model="includeCharts"
                            class="mt-1 rounded border-white/30 text-[#002045] focus:ring-[#002045]" />
                        <div>
                            <p class="text-sm font-black">Inclure les graphiques détaillés</p>
                            <p class="text-[10px] text-white/70">Visualisations et KPI dynamiques inclus.</p>
                        </div>
                    </label>
                </div>

                {{-- ── Planification automatique ────────────────────────────────── --}}
                <div class="relative xl:col-span-12 overflow-hidden rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 rounded-l-[1.25rem] bg-[#002045]"></span>

                    <div class="mb-8 flex flex-col gap-4 pl-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Automatisation</p>
                            <h3 class="mt-1 text-xl font-black text-[#002045]">Planification automatique</h3>
                            <p class="mt-1 text-sm text-[#57657a]">Automatisez vos exports récurrents vers les parties prenantes.</p>
                        </div>

                        <label class="inline-flex cursor-pointer items-center gap-3">
                            <span class="text-sm font-black uppercase tracking-wider text-[#002045]">Activer</span>
                            <button type="button"
                                wire:click="$set('autoScheduleEnabled', {{ json_encode(!$autoScheduleEnabled) }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200 {{ $autoScheduleEnabled ? 'bg-[#002045]' : 'bg-[#c4c6cf]/60' }}">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition-transform duration-200 {{ $autoScheduleEnabled ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                            </button>
                        </label>
                    </div>

                    <div class="mb-8 grid grid-cols-1 gap-6 pl-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Fréquence de récurrence</label>
                            <select wire:model="scheduleFrequency"
                                class="w-full appearance-none rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10">
                                <option value="daily">Quotidienne</option>
                                <option value="weekly">Hebdomadaire</option>
                                <option value="monthly">Mensuelle</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Date et heure de la prochaine exécution</label>
                            <input type="datetime-local" wire:model="nextExecutionAt"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Adresse e-mail de destination</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-[#57657a]">@</span>
                                <input type="email" wire:model="scheduleEmail" placeholder="comptabilite@entreprise.com"
                                    class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] py-3 pl-9 pr-4 text-sm text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#c4c6cf]/10 pl-4 pt-8">
                        <div class="mb-5">
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Historique</p>
                            <h4 class="mt-1 text-base font-black text-[#002045]">Plans d'exportation actifs</h4>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#c4c6cf]/20 text-[10px] font-black uppercase tracking-[0.18em] text-[#74777f]">
                                        <th class="pb-4">Description</th>
                                        <th class="pb-4">Fréquence</th>
                                        <th class="pb-4">Prochaine exécution</th>
                                        <th class="pb-4">Statut</th>
                                        <th class="pb-4">Dernière génération</th>
                                        <th class="pb-4">Destination</th>
                                        <th class="pb-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#c4c6cf]/10">
                                    @foreach ($this->scheduledPlans as $plan)
                                        <tr class="transition-colors hover:bg-[#f8faff]/50">
                                            <td class="py-4 font-semibold text-[#002045]">{{ $plan['description'] }}</td>
                                            <td class="py-4 text-[#57657a]">{{ $plan['frequency'] }}</td>
                                            <td class="py-4 text-[#57657a]">{{ $plan['nextExecution'] }}</td>
                                            <td class="py-4">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $plan['statusClasses'] }}">
                                                    {{ $plan['status'] }}
                                                </span>
                                            </td>
                                            <td class="py-4 text-[#57657a]">{{ $plan['lastGenerated'] ?? 'Jamais' }}</td>
                                            <td class="py-4 text-[#57657a]">{{ $plan['email'] }}</td>
                                            <td class="py-4 text-right text-xs font-semibold text-[#002045]">Auto</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Submit ───────────────────────────────────────────────────────── --}}
            <div class="flex flex-col items-center pt-2">
                <div class="mb-6 flex w-full max-w-md items-center divide-x divide-[#c4c6cf]/20 rounded-full border border-[#c4c6cf]/30 bg-[#f8faff]">
                    <span class="flex flex-1 justify-center py-2.5 text-[10px] font-black uppercase tracking-widest text-[#002045]">Audit ready</span>
                    <span class="flex flex-1 justify-center py-2.5 text-[10px] font-black uppercase tracking-widest text-[#002045]">SSL Chiffré</span>
                    <span class="flex flex-1 justify-center py-2.5 text-[10px] font-black uppercase tracking-widest text-[#002045]">Export immédiat</span>
                </div>

                <button type="submit"
                    class="group relative w-full max-w-2xl overflow-hidden rounded-xl bg-[#002045] py-5 text-lg font-black tracking-[-0.02em] text-white transition hover:opacity-95 active:scale-[0.99]">
                    <span class="relative z-10 flex items-center justify-center gap-3">
                        Générer le rapport d'exportation
                        <span class="transition-transform group-hover:translate-x-1">→</span>
                    </span>
                </button>

                <p class="mt-4 text-xs text-[#57657a]/60">
                    Conformité RGPD et sécurité des données bancaires garantie
                </p>
            </div>
        </form>

        {{-- ── Aperçu du rapport généré ─────────────────────────────────────────── --}}
        @if ($reportReady)
            <section class="space-y-6">
                <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Résultats</p>
                            <h3 class="mt-1 text-2xl font-black tracking-[-0.02em] text-[#002045]">Aperçu du rapport</h3>
                        </div>
                        <span class="rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-black text-[#002045]">
                            {{ strtoupper($exportFormat) }}
                        </span>
                    </div>

                    @if ($generatedDownloadUrl)
                        <div class="mb-6 flex flex-col gap-4 rounded-xl bg-[#f8faff] p-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-black text-[#002045]">Export prêt au téléchargement</p>
                                <p class="text-xs text-[#57657a]">{{ $generatedReportName }} • Généré le {{ $generatedReportTimestamp }}</p>
                            </div>
                            <a href="{{ $generatedDownloadUrl }}"
                                class="inline-flex items-center justify-center rounded-xl bg-[#002045] px-5 py-2.5 text-sm font-black text-white transition hover:opacity-90">
                                Télécharger l'export
                            </a>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($reportSummary as $item)
                            <div class="rounded-xl bg-[#f8faff] p-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">{{ $item['label'] }}</p>
                                <p class="mt-2 text-xl font-black {{ $item['tone'] }}">{{ $item['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                    <div class="mb-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Contenu</p>
                        <h3 class="mt-1 text-lg font-black text-[#002045]">Lignes incluses</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse ($previewRows as $row)
                            <div class="flex flex-col justify-between gap-3 rounded-xl bg-[#f8faff] p-4 md:flex-row md:items-center">
                                <div>
                                    <p class="text-sm font-black text-[#002045]">{{ $row['title'] }}</p>
                                    <p class="text-xs text-[#57657a]">{{ $row['subtitle'] }} · {{ $row['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-[#002045]">{{ $row['amount'] }}</p>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#57657a]">{{ $row['badge'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-[#57657a]">Aucune ligne disponible pour cette période.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

    </div>
</x-filament-panels::page>
