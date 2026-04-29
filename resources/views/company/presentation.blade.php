<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($company?->company_name ?: config('app.name')) . ' — Présentation' }}</title>
    <meta name="description"
        content="CROMMIX MALI — Vos solutions logicielles pour la transformation numérique des entreprises d'Afrique de l'Ouest.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
@php
    $companyName = $company?->company_name ?: 'CROMMIX MALI - SA';
    $companyEmail = $company?->email ?: '';
    $companyPhone = $company?->phone ?: '83 45 08 83 / 00226 25 50 20 00';
    $companyAddress = trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343';
    $companyWebsite = $company?->website ?: '';

@endphp

<body class="bg-[#f8f9ff] text-[#0b1c30] antialiased">
    <header class="sticky top-0 z-50 border-b border-[#c4c6cf]/20 bg-[#f8f9ff]/90 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
            <a href="#top" class="text-2xl font-black tracking-tight text-[#002045] uppercase">{{ $companyName }}</a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="#mission"
                    class="border-b-2 border-[#8df5e4] pb-1 text-sm font-medium text-[#002045]">Mission</a>
                <a href="#services"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Services</a>
                <a href="#portfolio"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Portfolio</a>
                <a href="#carrieres"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Carrières</a>
                <a href="#contact"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Contact</a>
            </div>

            <a href="#contact"
                class="rounded-lg bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                Demander une démo
            </a>
        </nav>
    </header>

    <main id="top">
        <section id="mission" class="relative overflow-hidden bg-[#f8f9ff]">
            <div
                class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-[#eff4ff] to-transparent lg:block">
            </div>
            <div
                class="mx-auto grid min-h-[780px] max-w-7xl grid-cols-1 items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:px-8">
                <div class="relative z-10">
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">
                        <span class="h-2 w-2 rounded-full bg-[#70d8c8]"></span>
                        Fondation de l'excellence
                    </div>

                    <h1
                        class="mb-6 max-w-3xl text-5xl font-extrabold leading-[1.05] tracking-tight text-[#002045] lg:text-7xl">
                        La précision dans l'architecture des ressources
                    </h1>

                    <p class="mb-10 max-w-xl text-xl leading-relaxed text-[#43474e]">
                        Le pilier architectural des entreprises modernes d'Afrique de l'Ouest. Nous transformons la
                        complexité opérationnelle en clarté stratégique.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="#services"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#002045] px-8 py-4 font-bold text-white transition hover:opacity-90">
                            Découvrir nos solutions
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="{{ route('company.presentation', ['intent' => 'Demande démo DMS']) }}#contact"
                            class="rounded-xl border border-[#c4c6cf]/40 bg-[#eff4ff] px-8 py-4 font-bold text-[#002045] transition hover:bg-white">
                            Request Demo
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="aspect-square overflow-hidden rounded-[2rem] shadow-2xl">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDFjWrLc0BmgWuJCi5p4thcvE0E6vCHLBgz58sUeKi-TVvnI1C-XAdBxWcT1iCGpymCSrNHp7ngeKhdMO16FO7en8XC1IyFtUTlMpB-_QpqS2T7Vpn--1ttZlrwBHzrVKyZxedWRp5Ph8or5-pmTjOveEN8b6eAkXYlJJV94PIZsQ-n5DiCSH7uzbkWmve02s4qn6ghvEAob0uocUgsifj2Qba7ADX6crUjVCqQwH928GWZ3B0tMfkIDFgg7MnLn04fczpB_R_5H6xZ"
                            alt="Immeuble corporate moderne" class="h-full w-full object-cover">
                    </div>

                    <div
                        class="absolute -bottom-8 -left-4 hidden max-w-xs rounded-[1.5rem] border border-[#c4c6cf]/20 bg-white/85 p-6 shadow-xl backdrop-blur-md md:block">
                        <div class="mb-2 text-4xl font-black text-[#005048]">99.9%</div>
                        <p class="text-sm font-medium text-[#43474e]">
                            Précision opérationnelle garantie dans nos déploiements ERP.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="philosophie" class="bg-[#eff4ff] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-16 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl">
                        <h2 class="mb-4 text-3xl font-black uppercase tracking-tight text-[#002045]">Notre Philosophie
                            Architecturale</h2>
                        <p class="text-[#43474e]">Nous ne construisons pas seulement des logiciels ; nous érigeons des
                            structures numériques pérennes.</p>
                    </div>
                    <div class="hidden h-px flex-1 bg-[#c4c6cf]/30 lg:block"></div>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <article class="relative overflow-hidden rounded-[1.5rem] bg-white p-10 shadow-sm">
                        <div class="absolute inset-y-0 left-0 w-1 bg-[#002045]"></div>
                        <div class="mb-6 text-3xl">🛡️</div>
                        <h3 class="mb-4 text-xl font-bold text-[#002045]">Intégrité</h3>
                        <p class="text-sm leading-relaxed text-[#43474e]">Une transparence absolue dans chaque
                            transaction et chaque ligne de code livrée.</p>
                    </article>

                    <article class="relative overflow-hidden rounded-[1.5rem] bg-white p-10 shadow-sm">
                        <div class="absolute inset-y-0 left-0 w-1 bg-[#70d8c8]"></div>
                        <div class="mb-6 text-3xl">💡</div>
                        <h3 class="mb-4 text-xl font-bold text-[#002045]">Innovation</h3>
                        <p class="text-sm leading-relaxed text-[#43474e]">Nous repoussons les limites technologiques
                            pour résoudre les défis locaux les plus concrets.</p>
                    </article>

                    <article class="relative overflow-hidden rounded-[1.5rem] bg-white p-10 shadow-sm">
                        <div class="absolute inset-y-0 left-0 w-1 bg-[#515f74]"></div>
                        <div class="mb-6 text-3xl">⚡</div>
                        <h3 class="mb-4 text-xl font-bold text-[#002045]">Efficacité</h3>
                        <p class="text-sm leading-relaxed text-[#43474e]">Des processus optimisés pour une croissance
                            rapide, durable et mesurable.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="services" class="bg-[#f8f9ff] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-4xl font-black tracking-tight text-[#002045]">Écosystème central</h2>
                    <p class="mx-auto max-w-2xl text-[#43474e]">Une suite intégrée de solutions conçues pour
                        l'interopérabilité totale.</p>
                </div>

                <div class="grid h-auto grid-cols-1 gap-4 md:grid-cols-4 md:grid-rows-2 md:h-[600px]">
                    <article
                        class="relative flex flex-col justify-between overflow-hidden rounded-[2rem] bg-[#1a365d] p-8 text-white md:col-span-2 md:row-span-2">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCi-xtgOWk5hDfEAKs7bQtg_Oc3F3anSiDhhH2GE2TgvLgQnwWQ40f8ZlGuKvGAsIBkSVFpLZK-HjQkpghvaKGZTSfLS2CRIqWBXr6_S4K2-p0iiOvDOGHJ03D9ksglYXCt2baSHEs-_OLU_irqHFHIj5_FHXZqVf6tc1n6FcFav9XWu0x-rh0o-VpxlsnPT4jQJiZ6i1rcdYzze4knt0BU-XH1iybMTlGJADbCkB2zQSCMpQ2muAUvM0xOIeLfCGLuUF7XjU4aVYri"
                            alt="Tableau de bord ERP"
                            class="absolute inset-0 h-full w-full object-cover opacity-20 mix-blend-overlay">
                        <div class="relative z-10">
                            <div class="mb-4 text-4xl text-[#8df5e4]">▦</div>
                            <h3 class="mb-4 text-3xl font-bold">ERP d'entreprise</h3>
                            <p class="leading-relaxed text-[#d6e3ff]">Gestion centralisée des ressources, de la finance
                                et des opérations sur une plateforme robuste.</p>
                        </div>
                        <div class="relative z-10 text-sm font-bold">En savoir plus →</div>
                    </article>

                    <article id="portfolio"
                        class="flex flex-col justify-between rounded-[2rem] bg-[#d3e4fe] p-8 md:col-span-2">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-[#002045]">Architecture web</h3>
                                <p class="mt-2 text-sm text-[#43474e]">Infrastructures scalables et interfaces haute
                                    performance.</p>
                            </div>
                            <div class="text-2xl text-[#002045]">🌐</div>
                        </div>
                        <div class="self-end text-4xl font-black text-[#002045]/10">WEB.CORE</div>
                    </article>

                    <article class="flex flex-col justify-between rounded-[2rem] bg-[#d5e3fc] p-8">
                        <div class="text-3xl text-[#002045]">🚚</div>
                        <h3 class="text-lg font-bold leading-tight text-[#002045]">Gestion de flotte</h3>
                    </article>

                    <article id="carrieres"
                        class="flex cursor-pointer flex-col justify-between rounded-[2rem] bg-[#8df5e4] p-8 transition hover:translate-x-1">
                        <h3 class="text-lg font-bold leading-tight text-[#002521]">Conseil stratégique</h3>
                        <div class="text-3xl text-[#002521]">→</div>
                    </article>
                </div>
            </div>
        </section>

        <section id="dms-products" class="bg-[#eff4ff] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <span
                        class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Nos
                        logiciels</span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-[#002045]">Solutions pour votre
                        transformation numérique</h2>
                    <p class="mt-3 mx-auto max-w-2xl text-[#43474e]">Des logiciels métier conçus pour les réalités des
                        entreprises d'Afrique de l'Ouest. D'autres solutions arrivent prochainement.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    {{-- DMS --}}
                    <article class="flex flex-col rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff]">
                        <div class="mb-4 flex items-center justify-between">
                            <span
                                class="rounded-lg bg-[#8df5e4]/30 px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#005048]">Disponible</span>
                            <span class="text-2xl">💊</span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#002045]">DMS</h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-[#43474e]">Gestion des
                            pharmacies</p>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-[#43474e]">Gestion officinale complète :
                            commandes, stock, facturation, assurances mutuelles et tableau de bord.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('dms.presentation') }}"
                                class="inline-flex items-center gap-1 rounded-lg bg-[#002045] px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">
                                Voir la présentation →
                            </a>
                            <a href="{{ route('company.presentation', ['intent' => 'Demande démo DMS']) }}#contact"
                                class="inline-flex items-center gap-1 rounded-lg border border-[#c4c6cf]/40 px-4 py-2 text-sm font-bold text-[#002045] transition hover:bg-[#eff4ff]">
                                Demander une démo
                            </a>
                        </div>
                    </article>

                    {{-- ERP --}}
                    <article class="flex flex-col rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff]">
                        <div class="mb-4 flex items-center justify-between">
                            <span
                                class="rounded-lg bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">Bientôt</span>
                            <span class="text-2xl">📊</span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#002045]">ERP</h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-[#43474e]">Gestion des
                            ressources d'entreprise</p>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-[#43474e]">Finances, facturation, projets,
                            achats et reporting — une plateforme unifiée pour piloter toute votre activité.</p>
                        <div class="mt-6">
                            <a href="{{ route('company.presentation', ['intent' => 'Implémentation ERP']) }}#contact"
                                class="inline-flex items-center gap-1 rounded-lg border border-[#c4c6cf]/40 px-4 py-2 text-sm font-bold text-[#002045] transition hover:bg-[#eff4ff]">
                                Nous contacter
                            </a>
                        </div>
                    </article>

                    {{-- Futur produit --}}
                    <article
                        class="flex flex-col rounded-2xl border-2 border-dashed border-[#c4c6cf]/40 bg-[#f8f9ff] p-7">
                        <div class="mb-4 flex items-center justify-between">
                            <span
                                class="rounded-lg bg-[#f8f9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#43474e]">En
                                développement</span>
                            <span class="text-2xl opacity-40">🔮</span>
                        </div>
                        <h3 class="text-2xl font-bold text-[#43474e]">Prochainement</h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-[#43474e]/60">Nouveaux
                            logiciels métier</p>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-[#43474e]/70">De nouvelles solutions
                            sectorielles sont en cours de développement. Laissez-nous vos coordonnées pour être informé
                            en priorité.</p>
                        <div class="mt-6">
                            <a href="{{ route('company.presentation', ['intent' => 'Autre Enquête']) }}#contact"
                                class="inline-flex items-center gap-1 rounded-lg border border-[#c4c6cf]/40 px-4 py-2 text-sm font-bold text-[#43474e] transition hover:bg-white">
                                Rester informé
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#002045] py-24 text-white">
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-16 px-6 lg:grid-cols-2 lg:px-8">
                <div class="relative z-10">
                    <h2 class="mb-8 text-4xl font-black tracking-tight">Conçu pour le contexte ouest-africain</h2>

                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#8df5e4]/20 text-[#8df5e4]">
                                📶</div>
                            <div>
                                <h4 class="mb-2 text-xl font-bold">Connectivité adaptative</h4>
                                <p class="text-sm text-[#d6e3ff]">Optimisé pour fonctionner de manière fluide, même dans
                                    des conditions de bande passante limitée.</p>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#8df5e4]/20 text-[#8df5e4]">
                                🌍</div>
                            <div>
                                <h4 class="mb-2 text-xl font-bold">Expertise locale</h4>
                                <p class="text-sm text-[#d6e3ff]">Conformité avec les régulations régionales et les
                                    pratiques commerciales du terrain.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative h-[400px]">
                    <div class="absolute inset-0 rotate-3 rounded-[40px] bg-white/10"></div>
                    <div class="absolute inset-0 -rotate-3 rounded-[40px] bg-white/10"></div>
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCKMv15cgHajpizXRY487byttw9r0z5OzXYZzOHks39RmNOrtptg_1YOcq6FfccTeZYYUOOIKgTVAiL4CUAAPcfyKD2DPCIHFrziifwr4F6a2ETH-jDVbc_s3k7ZGRNSAtohOOcIwr5WrWPDyr-VtVsUDnz5c9WjV4UcSNNirBTT-D-ZQWEP2KX4Fcf5tTfX_z-9Oj2-3q_-Mp7BaX480TcKDHU6GMyQg7QMLqjd1_VL0_l5lFN77-kBgNexLElJYG6GU2tFuv-tm5Y"
                        alt="Vue urbaine ouest-africaine"
                        class="absolute inset-0 h-full w-full rounded-[40px] object-cover shadow-2xl">
                </div>
            </div>
        </section>

        <section id="contact" class="bg-[#f8f9ff] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col gap-12 rounded-[2rem] bg-[#eff4ff] p-8 lg:flex-row lg:items-start lg:p-16">
                    <div class="lg:w-1/2">
                        <h2 class="mb-6 text-4xl font-black tracking-tight text-[#002045]">Établissez Votre Fondation
                        </h2>
                        <p class="mb-10 leading-relaxed text-[#43474e]">Prenez contact avec nos architectes de solutions
                            pour discuter de la transformation de vos opérations d'entreprise.</p>

                        @if (session('status'))
                            <div
                                class="mb-6 rounded-xl border border-[#70d8c8]/40 bg-white px-4 py-3 text-sm font-semibold text-[#005048]">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="space-y-5 text-sm font-medium text-[#0b1c30]">
                            <div class="flex items-center gap-4">
                                <span>📍</span>
                                <span>{{ $companyAddress }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span>✉️</span>
                                <span>{{ $companyEmail }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span>☎️</span>
                                <span>{{ $companyPhone }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span>🔗</span>
                                <span>{{ $companyWebsite }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/2">
                        <form method="POST" action="{{ route('company.presentation.contact') }}"
                            class="grid grid-cols-1 gap-6">
                            @csrf
                            <div class="space-y-1">
                                <label for="name" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Nom
                                    complet</label>
                                <input id="name" name="name" value="{{ old('name') }}" type="text"
                                    placeholder="Jean Dupont"
                                    class="w-full rounded-xl border border-transparent bg-white p-4 outline-none transition focus:border-[#002045]/15 focus:ring-2 focus:ring-[#002045]/10">
                                @error('name')
                                    <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1">
                                <label for="company_name"
                                    class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Entreprise <span
                                        class="normal-case font-normal opacity-60">(optionnel)</span></label>
                                <input id="company_name" name="company_name" value="{{ old('company_name') }}"
                                    type="text" placeholder="Nom de votre entreprise"
                                    class="w-full rounded-xl border border-transparent bg-white p-4 outline-none transition focus:border-[#002045]/15 focus:ring-2 focus:ring-[#002045]/10">
                                @error('company_name')
                                    <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                                @enderror
                            </div>
                            class="text-xs font-bold uppercase tracking-widest text-[#43474e]">E-mail
                            professionnel</label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email"
                                placeholder="j.dupont@entreprise.ci"
                                class="w-full rounded-xl border border-transparent bg-white p-4 outline-none transition focus:border-[#002045]/15 focus:ring-2 focus:ring-[#002045]/10">
                            @error('email')
                                <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="intent" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Intention
                            stratégique</label>
                        <select id="intent" name="intent"
                            class="w-full appearance-none rounded-xl border border-transparent bg-white p-4 outline-none transition focus:border-[#002045]/15 focus:ring-2 focus:ring-[#002045]/10">
                            <option value="Demande démo DMS" @selected(old('intent', request('intent')) === 'Demande démo DMS')>Demande démo DMS</option>
                            <option value="Implémentation ERP" @selected(old('intent') === 'Implémentation ERP')>
                                Implémentation ERP</option>
                            <option value="Consultation Digitale" @selected(old('intent', request('intent')) === 'Consultation Digitale')>Consultation digitale</option>
                            <option value="Gestion de Flotte" @selected(old('intent', request('intent')) === 'Gestion de Flotte')>
                                Gestion de flotte</option>
                            <option value="Autre Enquête" @selected(old('intent', request('intent')) === 'Autre Enquête')>
                                Autre
                                enquête</option>
                        </select>
                        @error('intent')
                            <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="message"
                            class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Message</label>
                        <textarea id="message" name="message" rows="4" placeholder="Décrivez brièvement votre besoin..."
                            class="w-full rounded-xl border border-transparent bg-white p-4 outline-none transition focus:border-[#002045]/15 focus:ring-2 focus:ring-[#002045]/10">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-[#002045] py-4 font-bold text-white shadow-lg shadow-[#002045]/10 transition hover:shadow-[#002045]/20">
                        Envoyer la demande
                    </button>
                    </form>
                </div>
            </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-[#c4c6cf]/10 bg-[#eff4ff] pb-10 pt-20">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-8">
            <div class="text-xl font-black text-[#002045] uppercase">{{ $companyName }}</div>
            <div
                class="flex flex-wrap justify-center gap-6 text-xs font-semibold uppercase tracking-widest text-[#43474e]">
                <a href="#" class="transition hover:text-[#005048]">Confidentialité</a>
                <a href="#" class="transition hover:text-[#005048]">Conditions</a>
                <a href="#" class="transition hover:text-[#005048]">Cookies</a>
                <a href="#" class="transition hover:text-[#005048]">Bureaux</a>
            </div>
            <div
                class="flex flex-col items-center gap-1 text-center text-[10px] uppercase tracking-wider text-[#43474e]/70">
                <span>© {{ now()->year }} {{ $companyName }}. Pour votre transformation numérique.</span>
                <span>En partenariat avec <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer"
                        class="underline transition hover:text-[#005048]">Crommix</a> — Burkina Faso</span>
            </div>
        </div>
    </footer>
</body>

</html>
