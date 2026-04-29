<x-filament-panels::page>
    @php
        $project = $this->getRecord()->loadMissing(['client', 'service', 'assignee', 'approver']);
        $progress = $this->getProgressPercentage();
        $noteEntries = $this->getNoteEntries();
        $relatedAttachments = $this->getRelatedAttachments();
        $relatedPayments = $this->getRelatedPayments();
        $relatedInvoices = $this->getRelatedInvoices();
        $relatedQuotes = $this->getRelatedQuotes();

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

    <div class="space-y-8 text-[#0b1c30] dark:text-white">
        <section
            class="relative overflow-hidden rounded-xl bg-[#eff4ff] p-8 text-[#0b1c30] shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:text-white dark:ring-white/10">
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
                        <h3 class="text-lg font-bold text-[#002045] dark:text-white">Progression du projet</h3>
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

                <section class="space-y-4">
                    <h3 class="px-2 text-xl font-bold text-[#002045] dark:text-white">Maillage opérationnel</h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="rounded-xl bg-[#eff4ff] p-6 dark:bg-slate-800/70">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-xs font-bold text-blue-800">DOC</div>
                                    <span class="font-bold text-[#002045] dark:text-white">Documents ({{ count($relatedAttachments) }})</span>
                                </div>
                                <a href="{{ url('/admin/documents') }}" class="text-xs font-bold uppercase text-[#002045] hover:underline dark:text-[#8df5e4]">Voir tout</a>
                            </div>

                            <div class="space-y-3">
                                @forelse ($relatedAttachments as $attachment)
                                    <div class="flex items-center justify-between rounded-xl border border-transparent bg-white p-3.5 transition-all hover:border-[#002045]/10 hover:shadow-sm dark:bg-gray-900">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-[10px] font-bold text-red-500">PDF</div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-[#002045] dark:text-white">{{ $attachment['name'] }}</span>
                                                <span class="text-[10px] font-medium text-[#57657a] dark:text-slate-400">{{ $attachment['meta'] }}</span>
                                            </div>
                                        </div>
                                        <a href="{{ $attachment['downloadUrl'] }}" class="rounded-full p-2 text-xs font-bold text-[#57657a] transition-all hover:bg-[#f8faff] hover:text-[#002045] dark:text-slate-300">DL</a>
                                    </div>
                                @empty
                                    <p class="text-sm text-[#57657a] dark:text-slate-400">Aucun document lié au projet.</p>
                                @endforelse
                            </div>

                            <a href="{{ url('/admin/documents') }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-[#002045] py-3 text-sm font-bold text-white transition-all hover:opacity-90">
                                Ajouter un document
                            </a>
                        </div>

                        <div class="rounded-xl bg-[#eff4ff] p-6 dark:bg-slate-800/70">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-100 text-xs font-bold text-teal-800">PAY</div>
                                    <span class="font-bold text-[#002045] dark:text-white">Paiements ({{ count($relatedPayments) }})</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @forelse ($relatedPayments as $payment)
                                    <div class="flex items-center justify-between rounded-xl bg-white p-3.5 dark:bg-gray-900">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#57657a] dark:text-slate-400">{{ $payment['label'] }}</span>
                                            <span class="text-lg font-bold text-[#002045] dark:text-white">{{ $payment['amount'] }}</span>
                                            <span class="text-[10px] text-[#57657a] dark:text-slate-400">{{ $payment['meta'] }}</span>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black {{ $payment['statusClasses'] }}">{{ $payment['status'] }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-[#57657a] dark:text-slate-400">Aucun paiement enregistré.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-xl bg-[#eff4ff] p-6 dark:bg-slate-800/70">
                            <div class="mb-6 flex items-center justify-between">
                                <span class="font-bold text-[#002045] dark:text-white">Factures ({{ count($relatedInvoices) }})</span>
                                <a href="{{ url('/admin/invoices') }}" class="text-xs font-bold uppercase text-[#002045] hover:underline dark:text-[#8df5e4]">Détails</a>
                            </div>

                            <div class="space-y-3">
                                @forelse ($relatedInvoices as $invoice)
                                    <div class="rounded-xl bg-white p-3.5 dark:bg-gray-900">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-bold text-[#002045] dark:text-white">{{ $invoice['number'] }}</p>
                                                <p class="text-[10px] text-[#57657a] dark:text-slate-400">{{ $invoice['meta'] }}</p>
                                            </div>
                                            <span class="text-sm font-black text-[#002045] dark:text-white">{{ $invoice['total'] }}</span>
                                        </div>
                                        <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-[#57657a] dark:text-slate-400">{{ $invoice['status'] }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-[#57657a] dark:text-slate-400">Aucune facture liée.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-xl bg-[#eff4ff] p-6 dark:bg-slate-800/70">
                            <div class="mb-6 flex items-center justify-between">
                                <span class="font-bold text-[#002045] dark:text-white">Devis ({{ count($relatedQuotes) }})</span>
                                <a href="{{ url('/admin/quotes') }}" class="text-xs font-bold uppercase text-[#002045] hover:underline dark:text-[#8df5e4]">Détails</a>
                            </div>

                            <div class="space-y-3">
                                @forelse ($relatedQuotes as $quote)
                                    <div class="rounded-xl bg-white p-3.5 dark:bg-gray-900">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-bold text-[#002045] dark:text-white">{{ $quote['number'] }}</p>
                                                <p class="text-[10px] text-[#57657a] dark:text-slate-400">{{ $quote['meta'] }}</p>
                                            </div>
                                            <span class="text-sm font-black text-[#002045] dark:text-white">{{ $quote['total'] }}</span>
                                        </div>
                                        <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-[#57657a] dark:text-slate-400">{{ $quote['status'] }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-[#57657a] dark:text-slate-400">Aucun devis lié.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-8 text-lg font-bold text-[#002045] dark:text-white">Historique et notes détaillées</h3>

                    <div class="relative space-y-6">
                        <div class="absolute bottom-2 left-4 top-2 w-0.5 bg-[#c4c6cf] opacity-20 dark:bg-white/20">
                        </div>

                        <div class="relative pl-12">
                            <div
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#d6e3ff] text-[10px] font-bold text-[#002045] dark:border-gray-900">
                                01</div>
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
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#d5e3fc] text-[10px] font-bold text-[#515f74] dark:border-gray-900">
                                02</div>
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
                                class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-[#eff4ff] text-[10px] font-bold text-[#43474e] dark:border-gray-900">
                                03</div>
                            <div class="mb-2">
                                <h4 class="font-bold text-[#002045] dark:text-white">Lancement du projet</h4>
                                <p class="text-xs text-[#43474e] dark:text-slate-400">Automatisé • {{ $startDate }}</p>
                            </div>
                        </div>

                        @foreach ($noteEntries as $entry)
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-sky-100 text-[10px] font-bold text-sky-700 dark:border-gray-900 dark:bg-sky-500/20 dark:text-sky-300">
                                    LOG</div>
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
                    <h3 class="mb-6 text-xs font-bold uppercase tracking-wider opacity-60">Équipe assignée</h3>
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
                    <h3 class="mb-6 text-sm font-bold text-[#002045] dark:text-white">Informations client</h3>
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
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="noteDate"
                                    class="mb-2 block text-sm font-medium text-[#002045] dark:text-white">Date de la note</label>
                                <input type="date" id="noteDate" wire:model.defer="noteDate"
                                    class="block w-full rounded-xl border border-[#c4c6cf]/30 bg-[#f8f9ff] px-3 py-2.5 text-sm text-[#0b1c30] shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white/10 dark:bg-slate-800 dark:text-white" />
                                @error('noteDate')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="noteUserId"
                                    class="mb-2 block text-sm font-medium text-[#002045] dark:text-white">Auteur</label>
                                <select id="noteUserId" wire:model.defer="noteUserId"
                                    class="block w-full rounded-xl border border-[#c4c6cf]/30 bg-[#f8f9ff] px-3 py-2.5 text-sm text-[#0b1c30] shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white/10 dark:bg-slate-800 dark:text-white">
                                    <option value="">— Sélectionner un utilisateur —</option>
                                    @foreach ($this->getAvailableUsers() as $u)
                                        <option value="{{ $u['id'] }}" @selected((int) $noteUserId === $u['id'])>{{ $u['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('noteUserId')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
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