<x-filament-panels::page>
    <div class="mx-auto max-w-6xl space-y-10 text-[#0b1c30]">
        <div class="mb-10">
            <div class="mb-2 flex items-center gap-3">
                <div class="h-8 w-1 rounded-full bg-[#002045]"></div>
                <h2 class="text-4xl font-extrabold tracking-tight text-[#002045] dark:text-white">Exportation Analytique
                    Financière</h2>
            </div>
            <p class="max-w-2xl text-lg leading-relaxed text-[#57657a] dark:text-[#74777f]">
                Configurez et générez vos rapports financiers approfondis. Choisissez les modules de données, la période
                et le format d'exportation pour vos audits et analyses stratégiques.
            </p>
        </div>

        <form wire:submit="generateReport" class="space-y-10">
            <div class="grid grid-cols-12 gap-6">
                <div
                    class="relative col-span-12 overflow-hidden rounded-xl bg-white p-8 shadow-[0_8px_32px_rgba(11,28,48,0.04)] dark:bg-slate-900 dark:ring-1 dark:ring-white/10 lg:col-span-7">
                    <div class="absolute left-0 top-0 h-full w-1 bg-[#002045]"></div>
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                            <span>📅</span>
                            Période d'Analyse
                        </h3>
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">Timeframe</span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-[#57657a] dark:text-[#74777f]">Du</label>
                            <input type="date" wire:model="startDate"
                                class="w-full rounded-lg border-none bg-[#f8faff] p-3 text-sm text-[#0b1c30] transition-all focus:ring-1 focus:ring-[#dce9ff] dark:bg-slate-800 dark:text-white" />
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-[#57657a] dark:text-[#74777f]">Au</label>
                            <input type="date" wire:model="endDate"
                                class="w-full rounded-lg border-none bg-[#f8faff] p-3 text-sm text-[#0b1c30] transition-all focus:ring-1 focus:ring-[#dce9ff] dark:bg-slate-800 dark:text-white" />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <button type="button" wire:click="usePreset('quarter')"
                            class="rounded bg-[#dce9ff] px-4 py-2 text-xs font-medium text-[#0b1c30] transition-all hover:bg-[#002045] hover:text-white">Dernier
                            Trimestre</button>
                        <button type="button" wire:click="usePreset('year')"
                            class="rounded bg-[#dce9ff] px-4 py-2 text-xs font-medium text-[#0b1c30] transition-all hover:bg-[#002045] hover:text-white">Année
                            Fiscale</button>
                    </div>
                </div>

                <div
                    class="relative col-span-12 overflow-hidden rounded-xl bg-[#f8faff] p-8 dark:bg-slate-900/70 lg:col-span-5">
                    <div class="absolute left-0 top-0 h-full w-1 bg-[#8df5e4]"></div>
                    <h3 class="mb-6 flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                        <span>📤</span>
                        Format d'Exportation
                    </h3>

                    <div class="space-y-4">
                        <label
                            class="group flex cursor-pointer items-center justify-between rounded-xl border border-transparent bg-white p-4 transition-all hover:border-[#c4c6cf]/20 hover:shadow-md dark:bg-slate-800">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                    PDF</div>
                                <div>
                                    <p class="text-sm font-bold text-[#002045] dark:text-white">PDF Document</p>
                                    <p class="text-[10px] text-[#57657a] dark:text-[#74777f]">Rapport visuel
                                        (.pdf)</p>
                                </div>
                            </div>
                            <input type="radio" wire:model="exportFormat" value="pdf"
                                class="h-4 w-4 text-[#002045] focus:ring-[#002045]" />
                        </label>

                        <label
                            class="group flex cursor-pointer items-center justify-between rounded-xl border border-transparent bg-white p-4 transition-all hover:border-[#c4c6cf]/20 hover:shadow-md dark:bg-slate-800">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                                    CSV</div>
                                <div>
                                    <p class="text-sm font-bold text-[#002045] dark:text-white">Données CSV</p>
                                    <p class="text-[10px] text-[#57657a] dark:text-[#74777f]">Analyse brute
                                        (.csv)</p>
                                </div>
                            </div>
                            <input type="radio" wire:model="exportFormat" value="csv"
                                class="h-4 w-4 text-[#002045] focus:ring-[#002045]" />
                        </label>
                    </div>
                </div>

                <div
                    class="relative col-span-12 overflow-hidden rounded-xl bg-white p-8 shadow-[0_8px_32px_rgba(11,28,48,0.04)] dark:bg-slate-900 dark:ring-1 dark:ring-white/10 lg:col-span-8">
                    <div class="absolute left-0 top-0 h-full w-1 bg-[#43af9f]"></div>
                    <h3 class="mb-6 flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                        <span>🧮</span>
                        Filtres de Données
                    </h3>

                    <div class="flex flex-wrap gap-4">
                        <label class="relative flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="selectedModules.revenue" class="peer sr-only" />
                            <div
                                class="flex items-center gap-2 rounded-full bg-[#f8faff] px-6 py-3 text-sm font-semibold text-[#002045] transition-all peer-checked:bg-[#002045] peer-checked:text-white dark:bg-slate-800 dark:text-slate-200">
                                <span>↗</span>
                                Chiffre d'Affaires
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="selectedModules.expenses" class="peer sr-only" />
                            <div
                                class="flex items-center gap-2 rounded-full bg-[#f8faff] px-6 py-3 text-sm font-semibold text-[#002045] transition-all peer-checked:bg-[#002045] peer-checked:text-white dark:bg-slate-800 dark:text-slate-200">
                                <span>↘</span>
                                Dépenses
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="selectedModules.payments" class="peer sr-only" />
                            <div
                                class="flex items-center gap-2 rounded-full bg-[#f8faff] px-6 py-3 text-sm font-semibold text-[#002045] transition-all peer-checked:bg-[#002045] peer-checked:text-white dark:bg-slate-800 dark:text-slate-200">
                                <span>💳</span>
                                Paiements
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="selectedModules.taxes" class="peer sr-only" />
                            <div
                                class="flex items-center gap-2 rounded-full bg-[#f8faff] px-6 py-3 text-sm font-semibold text-[#002045] transition-all peer-checked:bg-[#002045] peer-checked:text-white dark:bg-slate-800 dark:text-slate-200">
                                <span>🏦</span>
                                Taxes & TVA
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="selectedModules.audit" class="peer sr-only" />
                            <div
                                class="flex items-center gap-2 rounded-full bg-[#f8faff] px-6 py-3 text-sm font-semibold text-[#002045] transition-all peer-checked:bg-[#002045] peer-checked:text-white dark:bg-slate-800 dark:text-slate-200">
                                <span>🛡</span>
                                Audit & conformité
                            </div>
                        </label>
                    </div>
                    <p class="mt-4 text-xs text-[#57657a] dark:text-[#74777f]">Le format CSV inclut désormais un journal comptable structuré et une piste d’audit exploitable par les cabinets comptables.</p>
                </div>

                <div
                    class="relative col-span-12 flex flex-col justify-center overflow-hidden rounded-xl bg-[#002045] p-8 text-white lg:col-span-4">
                    <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-white/5"></div>
                    <div class="absolute -bottom-12 -left-12 h-24 w-24 rounded-full bg-white/5"></div>
                    <h3 class="mb-6 flex items-center gap-2 text-lg font-bold">
                        <span>⚙</span>
                        Options Avancées
                    </h3>

                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model="includeCharts"
                            class="mt-1 rounded border-white/30 text-[#002045] focus:ring-[#002045]" />
                        <div>
                            <p class="text-sm font-bold">Inclure les graphiques détaillés</p>
                            <p class="text-[10px] text-white/70">Visualisations et KPI dynamiques inclus.
                            </p>
                        </div>
                    </label>
                </div>

                <div
                    class="relative col-span-12 overflow-hidden rounded-xl bg-white p-8 shadow-[0_8px_32px_rgba(11,28,48,0.04)] dark:bg-slate-900 dark:ring-1 dark:ring-white/10">
                    <div class="absolute left-0 top-0 h-full w-1 bg-[#002045]"></div>

                    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-xl font-bold text-[#002045] dark:text-white">
                                <span>⏱</span>
                                Automatisation et Planification
                            </h3>
                            <p class="mt-1 text-sm text-[#57657a] dark:text-[#74777f]">Automatisez vos exports
                                récurrents vers les parties prenantes.</p>
                        </div>

                        <label class="inline-flex cursor-pointer items-center">
                            <span
                                class="mr-3 text-sm font-bold uppercase tracking-wider text-[#002045] dark:text-white">Activer
                                la planification automatique</span>
                            <div class="relative">
                                <input type="checkbox" wire:model.live="autoScheduleEnabled" class="peer sr-only" />
                                <div
                                    class="h-6 w-11 rounded-full bg-[#eff4ff] after:absolute after:inset-s-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[#002045] peer-checked:after:translate-x-full dark:bg-slate-700">
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                        <div class="space-y-4">
                            <label
                                class="text-xs font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">Fréquence
                                de récurrence</label>
                            <select wire:model="scheduleFrequency"
                                class="w-full appearance-none rounded-lg border-none bg-[#f8faff] p-3 text-sm text-[#0b1c30] focus:ring-1 focus:ring-[#dce9ff] dark:bg-slate-800 dark:text-white">
                                <option>Quotidienne</option>
                                <option>Hebdomadaire</option>
                                <option>Mensuelle</option>
                            </select>
                        </div>

                        <div class="space-y-4">
                            <label
                                class="text-xs font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">Date
                                et heure de la prochaine exécution</label>
                            <input type="datetime-local" wire:model="nextExecutionAt"
                                class="w-full rounded-lg border-none bg-[#f8faff] p-3 text-sm text-[#0b1c30] focus:ring-1 focus:ring-[#dce9ff] dark:bg-slate-800 dark:text-white" />
                        </div>

                        <div class="space-y-4">
                            <label
                                class="text-xs font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">Adresse
                                e-mail de destination</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-[#57657a] dark:text-[#74777f]">@</span>
                                <input type="email" wire:model="scheduleEmail" placeholder="comptabilite@entreprise.com"
                                    class="w-full rounded-lg border-none bg-[#f8faff] py-3 pl-8 pr-3 text-sm text-[#0b1c30] focus:ring-1 focus:ring-[#dce9ff] dark:bg-slate-800 dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#c4c6cf]/10 pt-8 dark:border-white/10">
                        <h4 class="mb-4 flex items-center gap-2 text-sm font-bold text-[#002045] dark:text-white">
                            <span>📋</span>
                            Plans d’exportation actifs
                        </h4>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-[#c4c6cf]/10 text-[10px] font-bold uppercase tracking-widest text-[#57657a] dark:border-white/10 dark:text-[#74777f]">
                                        <th class="px-4 pb-3">Description</th>
                                        <th class="px-4 pb-3">Fréquence</th>
                                        <th class="px-4 pb-3">Prochaine exécution</th>
                                        <th class="px-4 pb-3">Statut</th>
                                        <th class="px-4 pb-3">Dernière génération</th>
                                        <th class="px-4 pb-3">Destination</th>
                                        <th class="px-4 pb-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#c4c6cf]/10 dark:divide-white/10">
                                    @foreach ($this->scheduledPlans as $plan)
                                        <tr
                                            class="group transition-colors hover:bg-[#f8faff]/30 dark:hover:bg-slate-800/40">
                                            <td class="px-4 py-4 font-semibold text-[#002045] dark:text-white">
                                                {{ $plan['description'] }}</td>
                                            <td class="px-4 py-4">{{ $plan['frequency'] }}</td>
                                            <td class="px-4 py-4 text-[#57657a] dark:text-[#74777f]">
                                                {{ $plan['nextExecution'] }}</td>
                                            <td class="px-4 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $plan['statusClasses'] }}">
                                                    {{ $plan['status'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-[#57657a] dark:text-[#74777f]">
                                                {{ $plan['lastGenerated'] ?? 'Jamais' }}</td>
                                            <td class="px-4 py-4 text-[#57657a] dark:text-[#74777f]">
                                                {{ $plan['email'] }}</td>
                                            <td class="px-4 py-4 text-right">
                                                <span class="text-xs font-semibold text-[#002045] dark:text-slate-300">Auto</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center">
                <div
                    class="mb-8 flex w-full max-w-md items-center rounded-full border border-[#c4c6cf]/10 bg-[#f8faff]/50 p-1">
                    <div
                        class="flex flex-1 justify-center border-r border-[#c4c6cf]/20 py-2 text-[10px] font-bold uppercase tracking-widest text-[#002045]">
                        Audit ready</div>
                    <div
                        class="flex flex-1 justify-center border-r border-[#c4c6cf]/20 py-2 text-[10px] font-bold uppercase tracking-widest text-[#002045]">
                        SSL Encrypted</div>
                    <div
                        class="flex flex-1 justify-center py-2 text-[10px] font-bold uppercase tracking-widest text-[#002045]">
                        Instant Export</div>
                </div>

                <button type="submit"
                    class="group relative w-full max-w-2xl overflow-hidden rounded-xl bg-[#002045] py-6 text-lg font-extrabold tracking-tight text-white transition-all hover:shadow-[0_20px_50px_rgba(0,32,69,0.2)] active:scale-[0.99]">
                    <span class="relative z-10 flex items-center justify-center gap-3">
                        Générer le Rapport d’Exportation
                        <span class="transition-transform group-hover:translate-x-1">→</span>
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-[#002045] via-[#1a365d] to-[#002045] opacity-0 transition-opacity group-hover:opacity-100">
                    </div>
                </button>

                <p class="mt-6 flex items-center gap-2 text-xs text-[#57657a]/60 dark:text-[#57657a]">
                    <span>✔</span>
                    Conformité RGPD et sécurité des données bancaires garantie
                </p>
            </div>
        </form>

        @if ($reportReady)
            <section class="space-y-6">
                <div
                    class="rounded-xl bg-white p-8 shadow-[0_8px_32px_rgba(11,28,48,0.04)] ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-white/10">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-2xl font-extrabold text-[#002045] dark:text-white">Aperçu du rapport</h3>
                        <span
                            class="rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold text-[#002045]">{{ strtoupper($exportFormat) }}</span>
                    </div>

                    @if ($generatedDownloadUrl)
                        <div class="mb-6 flex flex-col gap-4 rounded-xl bg-[#f8faff] p-4 md:flex-row md:items-center md:justify-between dark:bg-slate-800">
                            <div>
                                <p class="text-sm font-bold text-[#002045] dark:text-white">Export prêt au téléchargement</p>
                                <p class="text-xs text-[#57657a] dark:text-[#74777f]">{{ $generatedReportName }} • Généré le {{ $generatedReportTimestamp }}</p>
                            </div>
                            <a href="{{ $generatedDownloadUrl }}"
                                class="inline-flex items-center justify-center rounded-lg bg-[#002045] px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">
                                Télécharger l’export
                            </a>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($reportSummary as $item)
                            <div class="rounded-xl bg-[#f8faff] p-4 dark:bg-slate-800">
                                <p
                                    class="text-xs font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">
                                    {{ $item['label'] }}</p>
                                <p class="mt-2 text-xl font-extrabold {{ $item['tone'] }} dark:text-white">{{ $item['value'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div
                    class="rounded-xl bg-white p-8 shadow-[0_8px_32px_rgba(11,28,48,0.04)] ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-white/10">
                    <h3 class="mb-6 text-lg font-bold text-[#002045] dark:text-white">Lignes incluses</h3>
                    <div class="space-y-3">
                        @forelse ($previewRows as $row)
                            <div
                                class="flex flex-col justify-between gap-3 rounded-xl bg-[#f8faff] p-4 md:flex-row md:items-center dark:bg-slate-800">
                                <div>
                                    <p class="text-sm font-bold text-[#002045] dark:text-white">{{ $row['title'] }}</p>
                                    <p class="text-xs text-[#57657a] dark:text-[#74777f]">{{ $row['subtitle'] }} •
                                        {{ $row['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-[#002045] dark:text-white">{{ $row['amount'] }}</p>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-widest text-[#57657a] dark:text-[#74777f]">
                                        {{ $row['badge'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-[#57657a] dark:text-[#74777f]">Aucune ligne disponible pour cette
                                période.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

        <footer
            class="mt-auto flex items-center justify-between border-t border-[#c4c6cf]/10 bg-[#f8faff]/30 px-4 py-6 dark:border-white/10">
            <div class="flex items-center gap-4">
                <span class="text-xs font-medium text-[#74777f]">© 2024 Crommix Forge - ERP Le Grand Registre</span>
            </div>
            <div class="flex gap-6">
                <a href="#" class="text-xs font-semibold text-[#57657a] transition-colors hover:text-[#002045]">Aide &
                    Guide</a>
                <a href="#" class="text-xs font-semibold text-[#57657a] transition-colors hover:text-[#002045]">Politique
                    de Confidentialité</a>
            </div>
        </footer>
    </div>
</x-filament-panels::page>