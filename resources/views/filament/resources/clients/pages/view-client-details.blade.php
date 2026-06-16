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

        $infoRows = [
            ['icon' => 'heroicon-o-user',       'label' => 'Contact principal', 'value' => $contactName],
            ['icon' => 'heroicon-o-envelope',    'label' => 'Email',             'value' => $email],
            ['icon' => 'heroicon-o-phone',       'label' => 'Téléphone',         'value' => $phone],
            ['icon' => 'heroicon-o-map-pin',     'label' => 'Localisation',      'value' => $location],
            ['icon' => 'heroicon-o-home-modern', 'label' => 'Adresse',           'value' => $address],
        ];
    @endphp

    <div class="space-y-6">

        {{-- ── Hero header ──────────────────────────────────────────────────── --}}
        <section class="rounded-[1.25rem] bg-[#eff4ff] p-8 ring-1 ring-[#dce9ff] shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-[#dce9ff] text-[#002045]">
                            {{ $this->getClientTypeLabel() }}
                        </span>
                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-[#8df5e4] text-[#005048]">
                            {{ $this->getClientStatusLabel() }}
                        </span>
                        <span class="rounded-full px-3 py-1 text-[10px] font-medium text-[#57657a]">
                            Réf #CLT-{{ str_pad((string) $client->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-black tracking-[-0.03em] text-[#002045]">{{ $displayName }}</h1>
                </div>

                <a href="{{ \App\Filament\Resources\Clients\ClientResource::getUrl('edit', ['record' => $client]) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-[#002045] px-6 py-2.5 text-sm font-black text-white shadow-sm transition hover:opacity-90">
                    Modifier la fiche
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-12">

            {{-- ── Contact details ──────────────────────────────────────────── --}}
            <section class="xl:col-span-8 rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Fiche client</p>
                        <h3 class="mt-1 text-lg font-black text-[#002045]">Coordonnées</h3>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach ($infoRows as $row)
                        <div class="flex items-start gap-4 rounded-xl bg-[#f8faff] px-4 py-3.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#eff4ff] text-[#002045]">
                                <x-dynamic-component :component="$row['icon']" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">{{ $row['label'] }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-[#0b1c30]">{{ $row['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── Sidebar: fiscal + notes ───────────────────────────────────── --}}
            <div class="xl:col-span-4 space-y-6">

                {{-- Fiscal profile --}}
                <section class="rounded-[1.25rem] bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] p-6 text-white shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Fiscalité</p>
                    <h3 class="mt-2 text-lg font-black">Profil fiscal</h3>
                    <div class="mt-5 space-y-3 border-t border-white/10 pt-5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/70">Profil</span>
                            <span class="text-sm font-black text-[#8df5e4]">{{ $taxLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/70">Taux</span>
                            <span class="text-sm font-black">{{ $taxRate }}</span>
                        </div>
                    </div>
                </section>

                {{-- Internal notes --}}
                <section class="rounded-[1.25rem] border border-[#c4c6cf]/30 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Interne</p>
                        <h3 class="mt-1 text-lg font-black text-[#002045]">Notes internes</h3>
                    </div>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-[#43474e]">{{ $notes }}</p>
                </section>
            </div>

        </div>
    </div>
</x-filament-panels::page>
