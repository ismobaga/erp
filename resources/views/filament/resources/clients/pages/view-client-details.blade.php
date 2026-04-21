<x-filament-panels::page>
    @php
        $client = $this->getRecord();
        $displayName = $client->company_name ?: $client->contact_name ?: 'Client sans nom';
        $contactName = $client->contact_name ?: 'Non renseigné';
        $email = $client->email ?: 'Non renseigné';
        $phone = $client->phone ?: 'Non renseigné';
        $address = $client->address ?: 'Non renseignée';
        $location = trim(collect([$client->city, $client->country])->filter()->implode(', ')) ?: 'Non renseignée';
        $notes = trim((string) $client->notes) !== '' ? $client->notes : 'Aucune note interne.';
        $taxProfile = $client->taxProfile();
        $taxLabel = $taxProfile['label'] ?? 'Profil standard';
        $taxRate = number_format((float) ($taxProfile['rate'] ?? 0), 2, ',', ' ') . ' %';
    @endphp

    <div class="space-y-6">
        <section class="rounded-xl bg-[#eff4ff] p-8 text-[#0b1c30] shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:text-white dark:ring-white/10">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-[#dce9ff] text-[#002045]">
                            {{ $this->getClientTypeLabel() }}
                        </span>
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-[#8df5e4] text-[#005048]">
                            {{ $this->getClientStatusLabel() }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#002045] dark:text-white">{{ $displayName }}</h1>
                    <p class="text-sm text-[#43474e] dark:text-slate-300">Référence #CLT-{{ str_pad((string) $client->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>

                <a href="{{ \App\Filament\Resources\Clients\ClientResource::getUrl('edit', ['record' => $client]) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-[#002045] px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#002045]/20 transition hover:opacity-90">
                    Modifier la fiche
                </a>
            </div>
        </section>

        <div class="grid grid-cols-12 gap-6">
            <section class="col-span-12 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-8">
                <h3 class="mb-4 text-lg font-bold text-[#002045] dark:text-white">Détails du client</h3>

                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">Contact principal</dt>
                        <dd class="text-sm font-medium text-[#002045] dark:text-white">{{ $contactName }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">Email</dt>
                        <dd class="text-sm font-medium text-[#002045] dark:text-white">{{ $email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">Téléphone</dt>
                        <dd class="text-sm font-medium text-[#002045] dark:text-white">{{ $phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">Localisation</dt>
                        <dd class="text-sm font-medium text-[#002045] dark:text-white">{{ $location }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-xs font-bold uppercase tracking-wider text-[#43474e] dark:text-slate-400">Adresse</dt>
                        <dd class="text-sm font-medium text-[#002045] dark:text-white">{{ $address }}</dd>
                    </div>
                </dl>
            </section>

            <section class="col-span-12 rounded-xl bg-[#eff4ff] p-6 shadow-sm ring-1 ring-[#dce9ff] dark:bg-slate-900 dark:ring-white/10 lg:col-span-4">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#002045] dark:text-white">Profil fiscal</h3>
                <p class="text-sm font-semibold text-[#002045] dark:text-white">{{ $taxLabel }}</p>
                <p class="text-xs text-[#43474e] dark:text-slate-300">{{ $taxRate }}</p>
            </section>

            <section class="col-span-12 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-lg font-bold text-[#002045] dark:text-white">Notes internes</h3>
                <p class="whitespace-pre-line text-sm text-[#43474e] dark:text-slate-300">{{ $notes }}</p>
            </section>
        </div>
    </div>
</x-filament-panels::page>
