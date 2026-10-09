@extends('layouts.public')

@section('title', 'DMS Crommix — Gestion des pharmacies en Afrique de l’Ouest')

@section('meta_description', 'DMS : solution complète de gestion pour pharmacies d’Afrique de l’Ouest. Commandes, stock, facturation, péremptions, assurances mutuelles.')

@section('content')
    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden border-b border-sand-200">
        <div class="site-container grid items-center gap-14 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <a href="{{ route('company.solutions') }}" class="inline-flex items-center gap-2 text-sm font-medium text-ink-600 transition hover:text-ink-900">
                    <x-site.icon name="arrow-right" class="h-4 w-4 rotate-180" /> Toutes les solutions
                </a>
                <div class="mt-8 flex items-center gap-3">
                    <span class="badge badge-available"><span class="h-1.5 w-1.5 rounded-full bg-kola-600"></span> Disponible</span>
                    <span class="text-sm font-medium text-ink-500">Drugstore Management System</span>
                </div>
                <h1 class="display mt-6 text-5xl sm:text-6xl">Transformez la gestion de votre pharmacie.</h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-600">
                    DMS : gestion intégrée des commandes, du stock, de la facturation et des assurances pour votre officine.
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="#contact" class="btn btn-accent">Demander une démo <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
                    <a href="#fonctionnalites" class="btn btn-outline">Voir les fonctionnalités</a>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -bottom-6 -right-6 h-2/3 w-2/3 rounded-[2rem] bg-bogolan" aria-hidden="true"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-sand-200 bg-sand-100 shadow-[0_40px_80px_-40px_rgb(46_38_32/0.5)]">
                    <img src="https://crommix.com/wp-content/dev/img/pharmacie.JPG" alt="Tableau de bord DMS en pharmacie"
                        class="aspect-[4/3] h-full w-full object-cover object-left" loading="eager">
                </div>
            </div>
        </div>
    </section>

    {{-- ── Fonctionnalités ──────────────────────────────────────────────── --}}
    <section id="fonctionnalites" class="scroll-mt-24 py-24">
        <div class="site-container">
            <div class="max-w-2xl">
                <p class="eyebrow">Fonctionnalités de DMS</p>
                <h2 class="display mt-5 text-4xl">Tout ce qu’il faut pour piloter l’officine.</h2>
                <p class="mt-5 text-ink-600">Une plateforme conçue spécifiquement pour les réalités des pharmacies d’Afrique de l’Ouest.</p>
            </div>
            @php
                $dmsFeatures = [
                    ['cart', 'Commandes intégrées', 'Gérez vos approvisionnements auprès des fournisseurs locaux et internationaux.'],
                    ['archive', 'Gestion du stock', 'Suivi en temps réel des stocks, alertes de rupture et valorisation des prix.'],
                    ['document', 'Facturation complète', 'Génération d’ordonnances et de factures conformes aux régulations locales.'],
                    ['calendar', 'Péremptions', 'Suivi automatique des dates de péremption et alertes avant expiration.'],
                    ['heart', 'Assurances mutuelles', 'Gestion des remboursements d’assurances et mutuelles de santé.'],
                    ['sparkles', 'Pharmacologie ML', 'Classification intelligente des médicaments avec recommandations.'],
                    ['shield', 'Rapports de conformité', 'Documents de conformité réglementaire pour les autorités sanitaires.'],
                    ['chart-bar', 'Tableau de bord', 'Indicateurs en temps réel de votre activité et de votre rentabilité.'],
                ];
            @endphp
            <div class="mt-14 grid gap-px overflow-hidden rounded-[1.5rem] border border-sand-200 bg-sand-200 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($dmsFeatures as [$icon, $title, $text])
                    <article class="bg-white p-7">
                        <span class="icon-tile"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                        <h3 class="mt-6 font-semibold text-ink-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Avantages ────────────────────────────────────────────────────── --}}
    <section id="avantages" class="bg-bogolan-dark py-24 text-sand-50">
        <div class="site-container grid gap-14 lg:grid-cols-2">
            <div>
                <p class="eyebrow eyebrow-light">Avantages</p>
                <h2 class="display mt-5 text-4xl text-sand-50 sm:text-5xl">Excellence & performance.</h2>
                <div class="mt-10 space-y-8">
                    @foreach ([
                        ['check', 'Confiance pharmaceutique', 'Tous les médicaments traçables et conformes aux standards de qualité africains.'],
                        ['trending-up', 'Croissance mesurable', 'Augmentez votre marge bénéficiaire de 15 % à 25 % grâce à l’optimisation des stocks.'],
                        ['lock', 'Sécurité des données', 'Chiffrement enterprise, sauvegarde cloud, conformité RGPD et données sensibles.'],
                    ] as [$icon, $title, $text])
                        <div class="flex gap-5">
                            <span class="icon-tile icon-tile-dark"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                            <div>
                                <h3 class="font-semibold text-sand-50">{{ $title }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-sand-200/75">{{ $text }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="self-center rounded-[1.5rem] border border-sand-50/10 bg-sand-50/5 p-8 sm:p-10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-sand-400">Performance démontrée</h3>
                <dl class="mt-8 space-y-7">
                    @foreach ([['98 %', 'Taux de satisfaction client', 98], ['3,2×', 'Retour sur investissement (année 1)', 64], ['15+', 'Pays d’Afrique de l’Ouest', 75]] as [$value, $label, $bar])
                        <div>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-sm text-sand-200/80">{{ $label }}</dt>
                                <dd class="display text-3xl text-sand-50">{{ $value }}</dd>
                            </div>
                            <div class="mt-3 h-1.5 rounded-full bg-sand-50/10">
                                <div class="h-1.5 rounded-full bg-terra-500" style="width: {{ $bar }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- ── Démarrage ────────────────────────────────────────────────────── --}}
    <section class="py-24">
        <div class="site-container">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Démarrer</p>
                <h2 class="display mt-5 text-4xl">Prêt à transformer votre pharmacie ?</h2>
                <p class="mt-5 text-ink-600">Rejoignez les centaines de pharmacies en Afrique de l’Ouest qui font confiance à DMS.</p>
            </div>
            <ol class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Essai gratuit', '30 jours sans engagement pour tester toutes les fonctionnalités.'],
                    ['Formation complète', 'Nous formons vos équipes à distance pour une utilisation optimale.'],
                    ['Support continu', 'Support en français 24h/24, 7j/7 pendant et après votre implémentation.'],
                ] as $i => [$title, $text])
                    <li class="card relative overflow-hidden">
                        <span class="display text-6xl text-terra-200">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-ink-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ── Démo ─────────────────────────────────────────────────────────── --}}
    <section id="contact" class="scroll-mt-24 pb-24">
        <div class="site-container">
            <div class="grid gap-12 rounded-[2rem] border border-sand-200 bg-white p-7 sm:p-10 lg:grid-cols-12 lg:p-14">
                <div class="lg:col-span-5">
                    <p class="eyebrow">Démo</p>
                    <h2 class="display mt-5 text-4xl">Demander une démo.</h2>
                    <p class="mt-5 leading-relaxed text-ink-600">Échangez avec nos experts DMS pour comprendre comment l’application peut transformer votre pharmacie.</p>
                    <ul class="mt-8 space-y-4 text-sm">
                        @foreach ([['map-pin', $companyAddress], ['mail', $companyEmail], ['phone', $companyPhone]] as [$icon, $value])
                            @if (filled($value))
                                <li class="flex items-center gap-3 font-medium text-ink-800">
                                    <span class="icon-tile h-9 w-9"><x-site.icon :name="$icon" class="h-4 w-4" /></span> {{ $value }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                <div class="lg:col-span-7">
                    <x-site.contact-form source="dms" default="Demande démo DMS"
                        company-label="Nom de la pharmacie" company-placeholder="Pharmacie Centrale"
                        intent-label="Type de pharmacie" submit-label="Demander une démo"
                        :intents="[
                            'Demande démo DMS' => 'Officine communautaire',
                            'Pharmacie Hospitalière' => 'Pharmacie hospitalière',
                            'Pharmacie Clinique' => 'Pharmacie clinique privée',
                            'Chaîne Pharmacies' => 'Chaîne de pharmacies',
                        ]" />
                </div>
            </div>
        </div>
    </section>
@endsection
