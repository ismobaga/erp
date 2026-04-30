@extends('layouts.public')

@section('title', 'DMS Crommix — Gestion des Pharmacies en Afrique de l\'Ouest')

@section('meta_description', 'DMS : Solution complète de gestion pour pharmacies d\'Afrique de l\'Ouest. Commandes, stock, facturation, péremptions, assurances mutuelles.')

@section('nav_links')
    <a href="#fonctionnalites"
        class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Fonctionnalités</a>
    <a href="#avantages" class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Avantages</a>
    <a href="#contact" class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Contact</a>
@endsection

@push('styles')
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script>
        tailwind.config = {
            theme: {
                colors: {
                    'primary': '#002045',
                    'tertiary': '#005048',
                    'accent': '#70d8c8',
                    'surface': '#f8f9ff',
                    'panel': '#eff4ff',
                    'ink': '#0b1c30',
                    'muted': '#43474e',
                }
            }
        }
    </script>
@endpush

@section('content')
    <section class="relative overflow-hidden bg-surface">
        <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-panel to-transparent lg:block"></div>
        <div class="mx-auto grid min-h-[780px] max-w-7xl grid-cols-1 items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:px-8">
            <div class="relative z-10">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-blue-900">
                    <span class="h-2 w-2 rounded-full bg-accent"></span>
                    Solution Complète
                </div>

                <h1 class="mb-6 max-w-3xl text-5xl font-extrabold leading-[1.05] tracking-tight text-primary lg:text-7xl">
                    Transformez votre pharmacie en Afrique de l'Ouest
                </h1>

                <p class="mb-10 max-w-xl text-xl leading-relaxed text-muted">
                    DMS : Gestion intégrée des commandes, du stock, de la facturation et des assurances pour votre officine.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="#contact"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-4 font-bold text-white transition hover:opacity-90">
                        Demander une démo
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('company.presentation') }}"
                        class="rounded-xl border border-slate-300 bg-panel px-8 py-4 font-bold text-primary transition hover:bg-white">
                        Retour à l'accueil
                    </a>
                </div>
            </div>

            <div class="relative">
                <div class="aspect-square overflow-hidden rounded-[2rem] shadow-2xl">
                    <img src="https://crommix.com/wp-content/dev/img/pharmacie.JPG" alt="Tableau de bord DMS"
                        class="h-full w-full object-cover object-left">
                </div>
            </div>
        </div>
    </section>

    <section id="fonctionnalites" class="bg-surface py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-16 text-center">
                <h2 class="mb-4 text-4xl font-black tracking-tight text-primary">Fonctionnalités de DMS</h2>
                <p class="mx-auto max-w-2xl text-muted">Une plateforme conçue spécifiquement pour les réalités des
                    pharmacies d'Afrique de l'Ouest.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                @php
                    $dmsFeatures = [
                        ['title' => 'Commandes Intégrées', 'description' => 'Gérez vos approvisionnements auprès des fournisseurs locaux et internationaux.'],
                        ['title' => 'Gestion du Stock', 'description' => 'Suivi en temps réel des stocks, alertes de rupture et valuation des prix.'],
                        ['title' => 'Facturation Complète', 'description' => 'Génération d\'ordonnances et de factures conformes aux régulations locales.'],
                        ['title' => 'Péremptions', 'description' => 'Suivi automatique des dates de péremption et alertes avant expiration.'],
                        ['title' => 'Assurances Mutuelles', 'description' => 'Gestion des remboursements d\'assurances et mutuelles de santé.'],
                        ['title' => 'Pharmacologie ML', 'description' => 'Classification intelligente des médicaments avec recommandations.'],
                        ['title' => 'Rapports Conformité', 'description' => 'Documents de conformité réglementaire pour autorités sanitaires.'],
                        ['title' => 'Tableau de Bord', 'description' => 'KPIs en temps réel de votre activité et rentabilité.'],
                    ];
                @endphp

                @foreach ($dmsFeatures as $feature)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                        <h3 class="mb-3 text-lg font-bold text-primary">{{ $feature['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-muted">{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="avantages" class="bg-panel py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                <div>
                    <h2 class="mb-8 text-4xl font-black tracking-tight text-primary">Excellence & Performance</h2>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/20 text-accent">
                                <span class="material-symbols-outlined">check_circle</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-primary">Confiance Pharmaceutique</h3>
                                <p class="text-sm text-muted">Tous les médicaments traçables et conformes aux standards de
                                    qualité africains.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/20 text-accent">
                                <span class="material-symbols-outlined">trending_up</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-primary">Croissance Mesurable</h3>
                                <p class="text-sm text-muted">Augmentez votre marge bénéficiaire de 15% à 25% grâce à
                                    l'optimisation des stocks.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/20 text-accent">
                                <span class="material-symbols-outlined">security</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-primary">Sécurité des Données</h3>
                                <p class="text-sm text-muted">Chiffrement enterprise, sauvegarde cloud, conformité RGPD et
                                    données sensibles.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-8 shadow-lg">
                    <h3 class="mb-6 text-2xl font-bold text-primary">Performance Démontrée</h3>
                    <div class="space-y-4">
                        <div class="flex items-end gap-2">
                            <div class="flex-1 h-12 bg-accent rounded-t-lg"></div>
                            <p class="text-sm font-bold text-primary">98%</p>
                        </div>
                        <p class="text-xs text-muted">Taux de satisfaction client</p>

                        <div class="h-px bg-slate-200 my-6"></div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1 h-8 bg-accent/70 rounded-t-lg"></div>
                            <p class="text-sm font-bold text-primary">3.2x</p>
                        </div>
                        <p class="text-xs text-muted">Retour sur investissement (année 1)</p>

                        <div class="h-px bg-slate-200 my-6"></div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1 h-10 bg-accent/50 rounded-t-lg"></div>
                            <p class="text-sm font-bold text-primary">15+</p>
                        </div>
                        <p class="text-xs text-muted">Pays d'Afrique de l'Ouest</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-surface py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="mb-4 text-4xl font-black tracking-tight text-primary">Prêt à transformer votre pharmacie ?</h2>
                <p class="mx-auto max-w-2xl text-muted">Rejoignez les centaines de pharmacies en Afrique de l'Ouest qui font
                    confiance à DMS.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-2xl border-2 border-primary/20 bg-white p-8 text-center">
                    <p class="mb-4 text-4xl font-black text-primary">1</p>
                    <h3 class="mb-2 font-bold text-primary">Essai Gratuit</h3>
                    <p class="text-sm text-muted">30 jours sans engagement pour tester toutes les fonctionnalités.</p>
                </div>
                <div class="rounded-2xl border-2 border-primary/20 bg-white p-8 text-center">
                    <p class="mb-4 text-4xl font-black text-primary">2</p>
                    <h3 class="mb-2 font-bold text-primary">Formation Complète</h3>
                    <p class="text-sm text-muted">Nous formons vos équipes à distance pour une utilisation optimale.</p>
                </div>
                <div class="rounded-2xl border-2 border-primary/20 bg-white p-8 text-center">
                    <p class="mb-4 text-4xl font-black text-primary">3</p>
                    <h3 class="mb-2 font-bold text-primary">Support Continu</h3>
                    <p class="text-sm text-muted">Support en français 24h/24, 7j/7 pendant et après votre implémentation.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="bg-panel py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex flex-col gap-12 rounded-[2rem] bg-white p-8 lg:flex-row lg:items-start lg:p-16">
                <div class="lg:w-1/2">
                    <h2 class="mb-6 text-4xl font-black tracking-tight text-primary">Demander une Démo</h2>
                    <p class="mb-10 leading-relaxed text-muted">Échangez avec nos experts DMS pour comprendre comment
                        l'application peut transformer votre pharmacie.</p>

                    @if (session('status'))
                        <div
                            class="mb-6 rounded-xl border border-accent/40 bg-surface px-4 py-3 text-sm font-semibold text-tertiary">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="space-y-5 text-sm font-medium text-ink">
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
                    </div>
                </div>

                <div class="w-full lg:w-1/2">
                    <form method="POST" action="{{ route('company.presentation.contact') }}" class="grid grid-cols-1 gap-6">
                        @csrf

                        <div class="space-y-1">
                            <label for="name" class="text-xs font-bold uppercase tracking-widest text-muted">Nom
                                complet</label>
                            <input id="name" name="name" value="{{ old('name') }}" type="text" placeholder="Fatou Diallo"
                                class="w-full rounded-xl border border-slate-200 bg-surface p-4 outline-none transition focus:border-primary/15 focus:ring-2 focus:ring-primary/10">
                            @error('name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="company_name" class="text-xs font-bold uppercase tracking-widest text-muted">Nom de
                                la pharmacie <span class="normal-case font-normal opacity-60">(optionnel)</span></label>
                            <input id="company_name" name="company_name" value="{{ old('company_name') }}" type="text"
                                placeholder="Pharmacie Centrale"
                                class="w-full rounded-xl border border-slate-200 bg-surface p-4 outline-none transition focus:border-primary/15 focus:ring-2 focus:ring-primary/10">
                            @error('company_name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="email" class="text-xs font-bold uppercase tracking-widest text-muted">E-mail</label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email"
                                placeholder="f.diallo@pharmacie.com"
                                class="w-full rounded-xl border border-slate-200 bg-surface p-4 outline-none transition focus:border-primary/15 focus:ring-2 focus:ring-primary/10">
                            @error('email')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="intent" class="text-xs font-bold uppercase tracking-widest text-muted">Type de
                                pharmacie</label>
                            <select id="intent" name="intent"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-surface p-4 outline-none transition focus:border-primary/15 focus:ring-2 focus:ring-primary/10">
                                <option value="Demande démo DMS" @selected(old('intent', 'Demande démo DMS') === 'Demande démo DMS')>Officine communautaire</option>
                                <option value="Pharmacie Hospitalière" @selected(old('intent') === 'Pharmacie Hospitalière')>
                                    Pharmacie hospitalière</option>
                                <option value="Pharmacie Clinique" @selected(old('intent') === 'Pharmacie Clinique')>Pharmacie
                                    clinique privée</option>
                                <option value="Chaîne Pharmacies" @selected(old('intent') === 'Chaîne Pharmacies')>Chaîne de
                                    pharmacies</option>
                            </select>
                            @error('intent')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="message"
                                class="text-xs font-bold uppercase tracking-widest text-muted">Message</label>
                            <textarea id="message" name="message" rows="4" placeholder="Parlez-nous de votre contexte..."
                                class="w-full rounded-xl border border-slate-200 bg-surface p-4 outline-none transition focus:border-primary/15 focus:ring-2 focus:ring-primary/10">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <input type="hidden" name="source" value="dms">

                        <button type="submit"
                            class="rounded-xl bg-primary py-4 font-bold text-white shadow-lg shadow-primary/10 transition hover:shadow-primary/20">
                            Demander une démo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection