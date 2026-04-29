<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $initials = collect(explode(' ', $record->name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
        $statusLabel = match ($record->status) { 'new' => 'Nouveau', 'read' => 'Lu', 'archived' => 'Archivé', default => $record->status};
        $statusColor = match ($record->status) { 'new' => 'bg-[#8df5e4] text-[#005048]', 'archived' => 'bg-[#c4c6cf]/30 text-[#43474e]', default => 'bg-[#dce9ff] text-[#2d476f]'};
        $intentColors = [
            'Demande démo DMS' => 'bg-[#dce9ff] text-[#002045]',
            'Implémentation ERP' => 'bg-[#eff4ff] text-[#2d476f]',
            'Consultation Digitale' => 'bg-[#8df5e4]/30 text-[#005048]',
            'Gestion de Flotte' => 'bg-[#d5e3fc] text-[#3a485b]',
            'Autre Enquête' => 'bg-[#c4c6cf]/20 text-[#43474e]',
        ];
        $intentColor = $intentColors[$record->intent] ?? 'bg-[#eff4ff] text-[#43474e]';
        $refNumber = 'INQ-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="grid grid-cols-12 gap-8">

        {{-- ── Left sidebar ────────────────────────────────────── --}}
        <div class="col-span-12 space-y-6 lg:col-span-4">

            {{-- Client card --}}
            <div
                class="relative overflow-hidden rounded-xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-900 dark:ring-white/10">
                {{-- accent pillar --}}
                <div class="absolute inset-y-0 left-0 w-1 bg-[#70d8c8]"></div>

                <p class="mb-6 text-[10px] font-black uppercase tracking-widest text-[#43474e]">Informations client</p>

                {{-- Avatar + name --}}
                <div class="mb-6 flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1a365d] text-lg font-bold text-[#adc7f7]">
                        {{ $initials }}
                    </div>
                    <div>
                        <p class="font-bold text-[#002045] dark:text-white">{{ $record->name }}</p>
                        @if($record->company_name)
                            <p class="text-sm text-[#43474e]">{{ $record->company_name }}</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 border-t border-[#dce9ff] pt-6 dark:border-white/10">
                    @if($record->company_name)
                        <div>
                            <p class="mb-0.5 text-[10px] font-black uppercase tracking-tighter text-[#43474e]">Société</p>
                            <p class="font-medium text-[#002045] dark:text-white">{{ $record->company_name }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="mb-0.5 text-[10px] font-black uppercase tracking-tighter text-[#43474e]">E-mail</p>
                        <a href="mailto:{{ $record->email }}"
                            class="font-medium text-[#002045] underline-offset-2 hover:underline dark:text-white">{{ $record->email }}</a>
                    </div>

                    <div>
                        <p class="mb-0.5 text-[10px] font-black uppercase tracking-tighter text-[#43474e]">Source</p>
                        <p class="font-medium capitalize text-[#002045] dark:text-white">{{ $record->source }}</p>
                    </div>

                    <div>
                        <p class="mb-0.5 text-[10px] font-black uppercase tracking-tighter text-[#43474e]">Reçu le</p>
                        <p class="font-medium text-[#002045] dark:text-white">
                            {{ $record->created_at->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                </div>

                {{-- Badges --}}
                <div class="mt-6 flex flex-wrap gap-2">
                    <span
                        class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider {{ $statusColor }}">
                        {{ $statusLabel }}
                    </span>
                    <span
                        class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider {{ $intentColor }}">
                        {{ $record->intent }}
                    </span>
                </div>
            </div>

            {{-- History timeline --}}
            <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-900 dark:ring-white/10">
                <p class="mb-6 text-center text-[10px] font-black uppercase tracking-widest text-[#43474e]">Historique
                </p>
                <div class="space-y-6">
                    <div class="relative flex items-start gap-4">
                        <div class="absolute left-[15px] top-8 h-full w-px bg-[#dce9ff] dark:bg-white/10"></div>
                        <div
                            class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-4 border-[#f8f9ff] bg-[#dce9ff] dark:border-gray-900 dark:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-[#002045]" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="pt-1">
                            <p class="text-sm font-medium text-[#0b1c30] dark:text-white">Demande créée via <span
                                    class="font-bold text-[#002045]">{{ ucfirst($record->source) }}</span></p>
                            <p class="mt-0.5 text-[11px] text-[#43474e]">{{ $record->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @if($record->status !== 'new')
                        <div class="relative flex items-start gap-4">
                            <div
                                class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-4 border-[#f8f9ff] bg-[#8df5e4]/30 dark:border-gray-900 dark:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-[#005048]" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div class="pt-1">
                                <p class="text-sm font-medium text-[#0b1c30] dark:text-white">Marqué comme <span
                                        class="font-bold text-[#005048]">{{ $statusLabel }}</span></p>
                                <p class="mt-0.5 text-[11px] text-[#43474e]">{{ $record->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Main column ─────────────────────────────────────── --}}
        <div class="col-span-12 space-y-6 lg:col-span-8">

            {{-- Reference header --}}
            <div class="flex items-end justify-between">
                <div>
                    <nav
                        class="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[#43474e]">
                        <span>Demandes</span>
                        <span>›</span>
                        <span>{{ $refNumber }}</span>
                    </nav>
                    <h2 class="text-3xl font-black tracking-tight text-[#002045] dark:text-white">{{ $record->intent }}
                    </h2>
                </div>
                <div class="flex gap-3">
                    {{-- Archive button handled via Filament header actions --}}
                </div>
            </div>

            {{-- Message card --}}
            <div
                class="rounded-xl bg-[#eff4ff] p-8 shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-800 dark:ring-white/10">
                <div class="mb-6 flex items-start justify-between">
                    <h3 class="text-lg font-bold text-[#002045] dark:text-white">Message original</h3>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-30 text-[#43474e]"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M3.691 6.292C5.094 4.771 7.217 4 10 4h1v2.819l-.804.161c-1.37.274-2.323.813-2.833 1.604A2.902 2.902 0 0 0 6.925 10H10a1 1 0 0 1 1 1v7c0 1.103-.897 2-2 2H3a1 1 0 0 1-1-1v-5l.003-2.919c-.009-.111-.199-2.741 1.688-4.789zm14 0C19.094 4.771 21.217 4 24 4h1v2.819l-.804.161c-1.37.274-2.323.813-2.833 1.604A2.902 2.902 0 0 0 20.925 10H24a1 1 0 0 1 1 1v7c0 1.103-.897 2-2 2h-5a1 1 0 0 1-1-1v-5l.003-2.919c-.009-.111-.199-2.741 1.688-4.789z" />
                    </svg>
                </div>
                @if($record->message)
                    <p class="whitespace-pre-line leading-relaxed text-[#0b1c30] dark:text-gray-200">{{ $record->message }}
                    </p>
                @else
                    <p class="italic text-[#43474e]">Aucun message joint à cette demande.</p>
                @endif
                <div class="mt-6 flex items-center gap-3 border-t border-[#dce9ff] pt-6 dark:border-white/10">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1a365d] text-xs font-bold text-[#adc7f7]">
                        {{ $initials }}
                    </div>
                    <div>
                        <p class="text-xs font-bold text-[#002045] dark:text-white">{{ $record->name }}</p>
                        <p class="text-[11px] text-[#43474e]">{{ $record->email }}</p>
                    </div>
                    <span
                        class="ml-auto text-[11px] text-[#43474e]">{{ $record->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
            </div>

            {{-- Quick reply --}}
            <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] dark:bg-gray-900 dark:ring-white/10">
                <div class="mb-6 flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-[#002045]"></div>
                    <h3 class="text-lg font-bold text-[#002045] dark:text-white">Répondre</h3>
                </div>
                <p class="mb-6 text-sm text-[#43474e]">
                    Répondez directement à <strong>{{ $record->name }}</strong> par e-mail.
                </p>
                <a href="mailto:{{ $record->email }}?subject=Re: {{ urlencode($record->intent) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#002045] px-6 py-3 text-sm font-bold text-white shadow transition hover:opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Envoyer un e-mail
                </a>
            </div>

        </div>
    </div>
</x-filament-panels::page>