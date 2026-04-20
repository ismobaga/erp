<x-filament-panels::page>
    @php
        $project = $this->getRecord()->loadMissing(['client', 'service', 'assignee', 'approver']);
        $progress = $this->getProgressPercentage();
        $noteEntries = $this->getNoteEntries();

        $statusMap = [
            'planned' => ['Planifié', 'bg-[#d5e3fc] text-[#2d476f]'],
            'in_progress' => ['En cours', 'bg-[#8df5e4] text-[#005048]'],
            'completed' => ['Terminé', 'bg-[#dce9ff] text-[#002045]'],
            'on_hold' => ['En pause', 'bg-[#ffdad6] text-[#93000a]'],
            'cancelled' => ['Annulé', 'bg-[#ffdad6] text-[#93000a]'],
        ];

        [$statusLabel, $statusClass] = $statusMap[$project->status] ?? ['En cours', 'bg-[#8df5e4] text-[#005048]'];

        $startDate = $project->start_date?->translatedFormat('d M Y') ?? 'Non défini';
        $dueDate = $project->due_date?->translatedFormat('d M Y') ?? 'Non défini';
        $remainingDays = $project->due_date ? now()->startOfDay()->diffInDays($project->due_date, false) : null;
        $remainingLabel = $remainingDays === null ? '—' : ($remainingDays >= 0 ? $remainingDays . ' jours' : 'Dépassé');
        $serviceName = $project->service?->name ?: 'Service non défini';
        $serviceType = $project->service?->category ?: $serviceName;
        $clientName = $project->client?->company_name ?: $project->client?->contact_name ?: 'Client non défini';
        $clientContact = $project->client?->contact_name ?: 'Contact à confirmer';
        $clientEmail = $project->client?->email ?: 'contact@client.tld';
        $clientLocation = trim(collect([$project->client?->city, $project->client?->country])->filter()->implode(', ')) ?: 'Localisation non précisée';
        $owner = $project->assignee?->name ?: 'Équipe projet';
        $priority = $remainingDays !== null && $remainingDays < 30 ? 'Critique (P1)' : 'Normale (P2)';
    @endphp

    <div class="space-y-8">
        <section
            class="relative overflow-hidden rounded-xl bg-[#eff4ff] p-8 text-[#0b1c30] shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:text-white dark:ring-white/10">
            <div class="absolute right-0 top-0 p-12 text-8xl opacity-5">📁</div>

            <div class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span
                            class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest {{ $statusClass }}">{{ $statusLabel }}</span>
                        <span class="text-sm font-medium text-[#74777f] dark:text-slate-300">
                            Ref: #PRJ-{{ str_pad((string) $project->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <div>
                        <h1 class="text-4xl font-extrabold tracking-tight text-[#002045] dark:text-white">
                            {{ $project->name }}</h1>
                        <p class="text-lg text-[#43474e] dark:text-slate-300">Client : {{ $clientName }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="rounded-xl border border-[#c4c6cf]/30 bg-white px-4 py-2 text-sm font-bold text-[#002045] shadow-sm dark:border-white/10 dark:bg-slate-800 dark:text-white">
                        {{ $serviceName }}
                    </span>
                    <a href="{{ url('/admin/projects/' . $project->id . '/edit') }}"
                        class="rounded-xl bg-[#002045] px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#002045]/20 transition hover:opacity-90">
                        Modifier
                    </a>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-12 space-y-8 lg:col-span-8">
                <section
                    class="relative overflow-hidden rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="absolute left-0 top-0 h-full w-1 bg-[#70d8c8]"></div>

                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                            <span class="text-[#005048]">📈</span>
                            Progression du Projet
                        </h3>
                        <span class="text-2xl font-black text-[#002045] dark:text-white">{{ $progress }}%</span>
                    </div>

                    <div class="mb-8 h-3 w-full rounded-full bg-[#eff4ff] dark:bg-slate-800">
                        <div class="h-full rounded-full bg-[#70d8c8]"
                            style="width: {{ $progress }}%; box-shadow: 0 0 12px rgba(141, 245, 228, 0.4);"></div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 border-t border-[#c4c6cf]/10 pt-8 md:grid-cols-3 dark:border-white/10">
                        <div>
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Date de début</p>
                            <p class="text-lg font-semibold text-[#002045] dark:text-white">{{ $startDate }}</p>
                        </div>
                        <div>
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Échéance</p>
                            <p class="text-lg font-semibold text-[#002045] dark:text-white">{{ $dueDate }}</p>
                        </div>
                        <div class="md:text-right">
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Temps restant</p>
                            <p
                                class="text-lg font-bold {{ $remainingDays !== null && $remainingDays < 0 ? 'text-[#ba1a1a]' : 'text-[#002045] dark:text-white' }}">
                                {{ $remainingLabel }}</p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-8 flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                        <span class="text-[#86a0cd]">🕘</span>
                        Historique & Notes détaillées
                    </h3>

                    <div class="relative space-y-6">
                        <div class="absolute bottom-2 left-[15px] top-2 w-0.5 bg-[#c4c6cf] opacity-20 dark:bg-white/20">
                        </div>

                        <div class="relative pl-12">
                            <div
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#d6e3ff] text-xs text-[#002045] dark:border-gray-900">
                                ✓</div>
                            <div class="mb-2 flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-bold text-[#002045] dark:text-white">Phase active :
                                        {{ $statusLabel }}</h4>
                                    <p class="text-xs text-[#43474e] dark:text-slate-400">Par {{ $owner }} • Mise à jour
                                        récente</p>
                                </div>
                                <span class="rounded bg-[#dce9ff] px-2 py-1 text-[10px] font-bold text-[#002045]">PROJET
                                    ACTIF</span>
                            </div>
                            <p class="max-w-2xl text-sm leading-relaxed text-[#43474e] dark:text-slate-300">
                                {{ $project->description ?: 'Le projet suit sa feuille de route opérationnelle et reste aligné avec les objectifs métiers du client.' }}
                            </p>
                        </div>

                        <div class="relative pl-12">
                            <div
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#d5e3fc] text-xs text-[#515f74] dark:border-gray-900">
                                ✎</div>
                            <div class="mb-2">
                                <h4 class="font-bold text-[#002045] dark:text-white">Périmètre de service</h4>
                                <p class="text-xs text-[#43474e] dark:text-slate-400">{{ $serviceName }} • Priorité
                                    {{ $priority }}</p>
                            </div>
                            <p class="max-w-2xl text-sm leading-relaxed text-[#43474e] dark:text-slate-300">
                                Le service assigné couvre les livrables essentiels pour atteindre les objectifs de
                                performance et de croissance du client.
                            </p>
                        </div>

                        <div class="relative pl-12">
                            <div
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#eff4ff] text-xs text-[#43474e] dark:border-gray-900">
                                ⚑</div>
                            <div class="mb-2">
                                <h4 class="font-bold text-[#002045] dark:text-white">Lancement du projet</h4>
                                <p class="text-xs text-[#43474e] dark:text-slate-400">Automatisé • {{ $startDate }}</p>
                            </div>
                        </div>

                        @foreach ($noteEntries as $entry)
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-sky-100 text-xs text-sky-700 dark:border-gray-900 dark:bg-sky-500/20 dark:text-sky-300">
                                    ✦</div>
                                <div
                                    class="rounded-xl border border-[#c4c6cf]/20 bg-[#f8f9ff] p-4 dark:border-white/10 dark:bg-slate-800/70">
                                    <div class="mb-2 flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="font-bold text-[#002045] dark:text-white">{{ $entry['title'] }}</h4>
                                            <p class="text-xs text-[#43474e] dark:text-slate-400">{{ $entry['meta'] }}</p>
                                        </div>
                                        <span
                                            class="rounded bg-[#dce9ff] px-2 py-1 text-[10px] font-bold text-[#002045]">JOURNAL</span>
                                    </div>
                                    <p
                                        class="whitespace-pre-line text-sm leading-relaxed text-[#43474e] dark:text-slate-300">
                                        {{ $entry['content'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-span-12 space-y-8 lg:col-span-4">
                <section class="rounded-xl bg-[#002045] p-8 text-white shadow-xl shadow-[#002045]/20">
                    <h3 class="mb-6 text-xs font-bold uppercase tracking-[0.2em] opacity-60">Équipe Assignée</h3>
                    <div class="group flex items-center gap-4">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#8df5e4] bg-[#1a365d] text-lg font-bold">
                            {{ strtoupper(substr($owner, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold leading-tight">{{ $owner }}</h4>
                            <p class="text-xs text-[#86a0cd]">Responsable du projet</p>
                        </div>
                    </div>
                    <div class="mt-8 space-y-4 border-t border-white/10 pt-6">
                        <div class="flex justify-between text-sm">
                            <span class="opacity-60">Type de Service</span>
                            <span class="font-bold text-[#8df5e4]">{{ $serviceType }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="opacity-60">Priorité</span>
                            <span class="font-bold">{{ $priority }}</span>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-[#eff4ff] p-8 text-[#0b1c30] shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:text-white dark:ring-white/10">
                    <h3 class="mb-6 flex items-center gap-2 text-sm font-bold text-[#002045] dark:text-white">
                        <span>🏢</span>
                        Informations Client
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Entité</p>
                            <p class="font-bold text-[#002045] dark:text-white">{{ $clientName }}</p>
                        </div>
                        <div>
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Contact Principal</p>
                            <p class="font-medium text-[#002045] dark:text-white">{{ $clientContact }}</p>
                            <p class="text-xs text-[#43474e] dark:text-slate-400">{{ $clientEmail }}</p>
                        </div>
                        <div>
                            <p
                                class="mb-1 text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">
                                Localisation</p>
                            <p class="font-medium text-[#002045] dark:text-white">{{ $clientLocation }}</p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-4 text-lg font-bold text-[#002045] dark:text-white">Ajouter une note interne</h3>
                    <p class="mb-4 text-sm text-[#43474e] dark:text-slate-400">Cette note reste visible dans le panneau
                        de gestion du projet.</p>

                    <form wire:submit="saveInternalNote" class="space-y-4">
                        <div>
                            <label for="internalNote"
                                class="mb-2 block text-sm font-medium text-[#002045] dark:text-white">Nouvelle
                                note</label>
                            <textarea id="internalNote" wire:model.defer="internalNote" rows="6"
                                class="block w-full rounded-xl border border-[#c4c6cf]/30 bg-[#f8f9ff] px-3 py-2.5 text-sm text-[#0b1c30] shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white/10 dark:bg-slate-800 dark:text-white"
                                placeholder="Exemple : réunion validée, livrable transmis, attente de retour client..."></textarea>
                            @error('internalNote')
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-filament::button type="submit" icon="heroicon-o-chat-bubble-left-ellipsis" class="w-full">
                            Enregistrer la note
                        </x-filament::button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>