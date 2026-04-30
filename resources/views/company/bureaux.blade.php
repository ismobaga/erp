@extends('layouts.public')

@section('title', 'Nos bureaux — ' . $companyName)
@section('meta_description', 'Retrouvez les bureaux et coordonnées de ' . $companyName . ' en Afrique de l\'Ouest.')

@section('nav_links')
    <a href="{{ route('company.presentation') }}"
        class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Accueil</a>
    <a href="{{ route('company.presentation') }}#contact"
        class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Contact</a>
@endsection

@section('content')
    <div class="mx-auto max-w-5xl px-6 py-20 lg:px-8">

        <div class="mb-16 text-center">
            <span
                class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Présence
                régionale</span>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-[#002045]">Nos bureaux</h1>
            <p class="mt-4 mx-auto max-w-xl text-[#43474e]">Au cœur de l'Afrique de l'Ouest, nos équipes vous accompagnent
                depuis deux pays pour servir toute la région.</p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

            {{-- Bureau principal : Mali --}}
            <article class="relative overflow-hidden rounded-[2rem] bg-white p-10 shadow-sm ring-1 ring-[#dce9ff]">
                <div class="absolute inset-y-0 left-0 w-1 bg-[#002045]"></div>
                <div class="mb-6 flex items-center gap-3">
                    <span class="text-3xl">🇲🇱</span>
                    <div>
                        <h2 class="text-xl font-black text-[#002045]">{{ $companyName }}</h2>
                        <p class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Siège social — Mali</p>
                    </div>
                </div>

                <div class="space-y-4 text-sm text-[#0b1c30]">
                    @if($companyAddress)
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">📍</span>
                            <span>{{ $companyAddress }}</span>
                        </div>
                    @else
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">📍</span>
                            <span>Bamako (République du Mali)<br>Bacodjicoroni Golf, Rue 661 Porte 343</span>
                        </div>
                    @endif

                    @if($companyPhone)
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">☎️</span>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $companyPhone) }}"
                                class="transition hover:text-[#005048]">{{ $companyPhone }}</a>
                        </div>
                    @else
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">☎️</span>
                            <span>+223 83 45 08 83</span>
                        </div>
                    @endif

                    @if($companyEmail)
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">✉️</span>
                            <a href="mailto:{{ $companyEmail }}" class="transition hover:text-[#005048]">{{ $companyEmail }}</a>
                        </div>
                    @endif

                    @if($companyWebsite)
                        <div class="flex gap-3">
                            <span class="mt-0.5 text-[#005048]">🔗</span>
                            <a href="{{ $companyWebsite }}" target="_blank" rel="noopener noreferrer"
                                class="transition hover:text-[#005048]">{{ $companyWebsite }}</a>
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <span
                        class="rounded-lg bg-[#8df5e4]/30 px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#005048]">Siège
                        social</span>
                    <span
                        class="rounded-lg bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">ERP
                        · Web · Conseil</span>
                </div>
            </article>

            {{-- Bureau partenaire : Burkina Faso --}}
            <article class="relative overflow-hidden rounded-[2rem] bg-white p-10 shadow-sm ring-1 ring-[#dce9ff]">
                <div class="absolute inset-y-0 left-0 w-1 bg-[#70d8c8]"></div>
                <div class="mb-6 flex items-center gap-3">
                    <span class="text-3xl">🇧🇫</span>
                    <div>
                        <h2 class="text-xl font-black text-[#002045]">Crommix</h2>
                        <p class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Bureau partenaire — Burkina
                            Faso</p>
                    </div>
                </div>

                <div class="space-y-4 text-sm text-[#0b1c30]">
                    <div class="flex gap-3">
                        <span class="mt-0.5 text-[#005048]">📍</span>
                        <span>Ouagadougou (Burkina Faso)</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="mt-0.5 text-[#005048]">☎️</span>
                        <span>+226 25 50 20 00</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="mt-0.5 text-[#005048]">🔗</span>
                        <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer"
                            class="transition hover:text-[#005048]">crommix.com</a>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <span
                        class="rounded-lg bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">Partenaire
                        régional</span>
                    <span
                        class="rounded-lg bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">DMS
                        · Logiciels métier</span>
                </div>
            </article>

        </div>

        {{-- Zone de couverture --}}
        <section class="mt-16 rounded-[2rem] bg-[#002045] px-10 py-12 text-white">
            <div class="grid grid-cols-1 items-center gap-10 md:grid-cols-2">
                <div>
                    <h2 class="text-3xl font-black tracking-tight">Zone de couverture</h2>
                    <p class="mt-4 text-[#d6e3ff]">Nos solutions sont déployées et supportées dans toute l'Afrique de
                        l'Ouest francophone, avec une expertise particulière sur :</p>
                    <ul class="mt-6 space-y-2 text-sm text-[#d6e3ff]">
                        <li class="flex items-center gap-2"><span class="text-[#8df5e4]">✓</span> Mali</li>
                        <li class="flex items-center gap-2"><span class="text-[#8df5e4]">✓</span> Burkina Faso</li>
                        <li class="flex items-center gap-2"><span class="text-[#8df5e4]">✓</span> Côte d'Ivoire</li>
                        <li class="flex items-center gap-2"><span class="text-[#8df5e4]">✓</span> Sénégal</li>
                        <li class="flex items-center gap-2"><span class="text-[#8df5e4]">✓</span> Niger · Guinée · Bénin
                        </li>
                    </ul>
                </div>
                <div class="text-center">
                    <div class="text-8xl font-black text-[#70d8c8]">🌍</div>
                    <p class="mt-4 text-sm font-semibold uppercase tracking-widest text-[#8df5e4]">Afrique de l'Ouest</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <div class="mt-12 rounded-2xl bg-[#eff4ff] p-8 text-center">
            <p class="text-sm font-medium text-[#43474e]">Vous souhaitez nous rendre visite ou discuter de votre projet ?
            </p>
            <a href="{{ route('company.presentation') }}#contact"
                class="mt-4 inline-block rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                Prendre contact
            </a>
        </div>

    </div>
@endsection