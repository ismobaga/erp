<x-filament-panels::page>
    <div class="space-y-8 text-[#0b1c30] dark:text-white">
        <section class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <h2 class="mb-1 text-3xl font-extrabold tracking-tight text-[#002045] dark:text-white">Gestion des
                    Documents</h2>
                <p class="font-medium text-[#43474e] dark:text-slate-400">Répertoire centralisé des actifs financiers et
                    juridiques.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button"
                    class="rounded-xl bg-[#eff4ff] px-6 py-3 font-semibold text-[#002045] transition-all hover:bg-[#dce9ff] dark:bg-slate-800 dark:text-white">
                    Filtres avancés
                </button>
                <a href="#upload-form"
                    class="rounded-xl bg-[#002045] px-6 py-3 font-bold text-white shadow-lg shadow-[#002045]/10 transition-all hover:opacity-90">
                    Télécharger un document
                </a>
            </div>
        </section>

        <div class="rounded-xl bg-[#eff4ff] p-4 shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:ring-white/10">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative w-full lg:max-w-md">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">🔎</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un document..."
                        class="w-full rounded-xl border border-[#c4c6cf]/20 bg-white py-2 pl-10 pr-4 text-sm text-[#0b1c30] outline-none ring-0 focus:border-[#002045] dark:border-white/10 dark:bg-slate-800 dark:text-white" />
                </div>

                <div class="flex flex-wrap gap-2 text-sm font-semibold">
                    <button wire:click="setFilterType('all')"
                        class="rounded-full px-3 py-1 {{ $this->filterType === 'all' ? 'bg-white text-[#002045] dark:bg-slate-800 dark:text-white' : 'text-slate-500' }}">Tout</button>
                    <button wire:click="setFilterType('pdf')"
                        class="rounded-full px-3 py-1 {{ $this->filterType === 'pdf' ? 'bg-white text-[#002045] dark:bg-slate-800 dark:text-white' : 'text-slate-500' }}">PDF</button>
                    <button wire:click="setFilterType('excel')"
                        class="rounded-full px-3 py-1 {{ $this->filterType === 'excel' ? 'bg-white text-[#002045] dark:bg-slate-800 dark:text-white' : 'text-slate-500' }}">Excel</button>
                    <button wire:click="setFilterType('docs')"
                        class="rounded-full px-3 py-1 {{ $this->filterType === 'docs' ? 'bg-white text-[#002045] dark:bg-slate-800 dark:text-white' : 'text-slate-500' }}">Docs</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-12 lg:col-span-8">
                <div
                    class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-900 dark:ring-white/10">
                    <div
                        class="flex items-center justify-between border-b border-[#eff4ff] bg-[#f8f9ff] p-6 dark:border-white/10 dark:bg-slate-950/50">
                        <h3 class="flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                            <span class="h-6 w-1 rounded-full bg-[#002045]"></span>
                            Documents Récents
                        </h3>
                        <span class="text-sm font-semibold text-[#43474e] dark:text-slate-400">{{ count($documents) }}
                            élément(s)</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead
                                class="bg-[#eff4ff] text-xs uppercase tracking-wider text-[#43474e] dark:bg-slate-900 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Nom du fichier</th>
                                    <th class="px-6 py-4 font-bold">Type</th>
                                    <th class="px-6 py-4 font-bold">Date</th>
                                    <th class="px-6 py-4 text-right font-bold">Taille</th>
                                    <th class="px-6 py-4 font-bold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse ($documents as $document)
                                    <tr
                                        class="border-b border-[#eff4ff] transition-colors odd:bg-white even:bg-[#f8f9ff] hover:bg-[#eff4ff] dark:border-white/10 dark:odd:bg-gray-900 dark:even:bg-slate-900/60 dark:hover:bg-slate-800/80">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-lg text-xs font-bold {{ $document['tint'] }}">
                                                    {{ $document['icon'] }}
                                                </div>
                                                <div>
                                                    <p class="font-bold text-[#002045] dark:text-white">
                                                        {{ $document['name'] }}</p>
                                                    <p class="text-xs text-[#43474e] dark:text-slate-400">
                                                        {{ $document['category'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span
                                                class="rounded-full bg-[#eff4ff] px-3 py-1 text-xs font-bold text-[#43474e] dark:bg-slate-800 dark:text-slate-300">{{ $document['type'] }}</span>
                                        </td>
                                        <td class="px-6 py-5 text-[#43474e] dark:text-slate-400">{{ $document['date'] }}
                                        </td>
                                        <td class="px-6 py-5 text-right font-mono text-sm text-[#002045] dark:text-white">
                                            {{ $document['size'] }}</td>
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ $document['downloadUrl'] }}"
                                                    class="font-semibold text-[#002045] hover:underline dark:text-[#8df5e4]">Télécharger</a>
                                                @if ($document['canDelete'])
                                                    <button type="button"
                                                        wire:click="deleteDocument({{ $document['id'] }})"
                                                        wire:confirm="Supprimer ce document ? Cette action est irréversible."
                                                        class="font-semibold text-rose-600 hover:underline dark:text-rose-400">
                                                        Supprimer
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-10 text-center text-sm text-[#43474e] dark:text-slate-400">
                                            Aucun document n’a encore été téléversé.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-span-12 space-y-8 lg:col-span-4">
                <div
                    class="relative overflow-hidden rounded-xl bg-[#002045] p-8 text-white shadow-xl shadow-[#002045]/20">
                    <div class="relative z-10">
                        <h4 class="mb-2 text-lg font-bold">Stockage utilisé</h4>
                        <p class="mb-6 text-sm text-[#adc7f7]">Vous utilisez {{ $storage['percent'] }}% de votre quota
                            entreprise.</p>
                        <div class="mb-4 text-3xl font-black">{{ $storage['used'] }} <span
                                class="text-lg font-medium opacity-70">/ {{ $storage['quota'] }}</span></div>
                        <div class="mb-8 h-3 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-[#8df5e4]" style="width: {{ $storage['percent'] }}%">
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl bg-[#eff4ff] p-6 shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:ring-white/10">
                    <h4 class="mb-6 flex items-center gap-2 text-lg font-bold text-[#002045] dark:text-white">
                        <span class="h-5 w-1 rounded-full bg-[#8df5e4]"></span>
                        Catégories
                    </h4>
                    <div class="space-y-3">
                        @foreach ($categories as $category)
                            <div
                                class="flex items-center justify-between rounded-xl bg-white p-3 ring-1 ring-[#dce9ff] dark:bg-slate-800 dark:ring-white/10">
                                <span class="font-semibold text-[#002045] dark:text-white">{{ $category['label'] }}</span>
                                <span
                                    class="rounded bg-[#eff4ff] px-2 py-0.5 text-sm font-mono text-[#43474e] dark:bg-slate-700 dark:text-slate-300">{{ $category['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="upload-form"
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-900 dark:ring-white/10">
                    <h4 class="mb-2 text-lg font-bold text-[#002045] dark:text-white">Nouveau document</h4>
                    <p class="mb-5 text-sm text-[#43474e] dark:text-slate-400">Ajoutez une pièce jointe dans l’archive
                        centralisée.</p>

                    <form wire:submit="uploadDocument" class="space-y-4">
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-[#002045] dark:text-white">Catégorie</label>
                            <select wire:model="documentCategory"
                                class="w-full rounded-xl border border-[#c4c6cf]/30 bg-[#f8f9ff] px-3 py-2.5 text-sm text-[#0b1c30] dark:border-white/10 dark:bg-slate-800 dark:text-white">
                                <option>Factures</option>
                                <option>Devis</option>
                                <option>Contrats</option>
                                <option>Reçus</option>
                                <option>Archives</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-[#002045] dark:text-white">Fichier</label>
                            <input type="file" wire:model="upload"
                                class="w-full rounded-xl border border-[#c4c6cf]/30 bg-[#f8f9ff] px-3 py-2.5 text-sm dark:border-white/10 dark:bg-slate-800" />
                            @error('upload')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-filament::button type="submit" class="w-full" icon="heroicon-o-arrow-up-tray">
                            Enregistrer le document
                        </x-filament::button>
                    </form>
                </div>
            </div>
        </div>

        <section>
            <div class="mb-8 flex items-center justify-between">
                <h3 class="text-2xl font-extrabold text-[#002045] dark:text-white">Exploration par type</h3>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($exploration as $item)
                    <div
                        class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-[#dce9ff] transition-all hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                        <div class="mb-6 flex items-start justify-between">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl font-bold {{ $item['tone'] }}">
                                {{ $item['count'] }}
                            </div>
                            <span class="text-slate-300">→</span>
                        </div>
                        <h4 class="mb-1 text-lg font-bold text-[#002045] dark:text-white">{{ $item['label'] }}</h4>
                        <p class="text-sm text-[#43474e] dark:text-slate-400">{{ $item['count'] }} fichier(s)</p>
                        <div
                            class="mt-4 border-t border-[#eff4ff] pt-4 text-xs font-bold uppercase tracking-widest text-slate-400 dark:border-white/10">
                            {{ $item['size'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>