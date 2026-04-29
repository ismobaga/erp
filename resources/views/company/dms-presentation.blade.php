<!DOCTYPE html>
<html class="scroll-smooth" lang="fr">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>{{ ($company?->company_name ?: 'DMS Ledger') . ' — La Révolution de la Gestion Officinale' }}</title>
    <meta name="description"
        content="DMS Ledger : solution de gestion officinale en français avec commandes, facturation, stocks, péremptions, AMO et extranet Pharma ML." />

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#002045',
                        tertiary: '#005048',
                        surface: '#f8f9ff',
                        panel: '#eff4ff',
                        ink: '#0b1c30',
                        muted: '#43474e',
                        accent: '#8df5e4'
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: Inter, sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
        }

        .status-pillar {
            width: 4px;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
        }
    </style>
</head>

@php
    $companyName = $company?->company_name ?: 'DMS Ledger';
    $companyEmail = $company?->email ?: 'contact@dms-ledger.com';
    $companyPhone = $company?->phone ?: '+225 00 00 00 00';
    $companyAddress = trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Abidjan, Côte d\'Ivoire';
    $companyWebsite = $company?->website ?: 'www.dms-ledger.com';

    $dmsFeatures = [
        'Commande - livraison - validation',
        'Facturation - encaissement - recouvrement',
        'Gestion des avoirs fournisseurs',
        'Stock Min - Max',
        'Gestion des produits (création, modification, suppression) avec zones de stockage, famille produit, DCI et unités de gestion',
        'Multi-magasins avec transfert de stocks et multi-caisses (magasin, salle de vente, parapharmacie...)',
        'Contrôle des péremptions (date sur code-barres) et détection des produits à date la plus proche à la vente',
        'Gestion intégrale automatique des bons AMO et autres assurances',
        'Extranet (commande et facture électronique) et intégration de la norme Pharma ML avec Ubipharm',
        'Inventaire tournant, inventaire complet et micro-inventaire progressif',
        'Archivage des données et restauration depuis le logiciel',
        '200 points de contrôle d\'accès',
        'Sauvegarde complète à chaque arrêt de session',
        'Espace statistique et états divers',
        'Mobile paiement, SMS pharmacie et alertes SMS',
    ];
@endphp

<body class="bg-surface text-ink selection:bg-accent selection:text-primary">
    <header class="fixed top-0 z-50 w-full border-b border-slate-200/40 bg-[#eff4ff]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-5 lg:px-8">
            <a href="#top" class="text-xl font-bold tracking-tight text-primary">{{ $companyName }}</a>

            <div class="hidden items-center gap-8 md:flex">
                <a class="border-b-2 border-accent pb-1 text-primary" href="#produit">Produit</a>
                <a class="font-medium text-slate-600 transition hover:text-primary"
                    href="#fonctionnalites">Fonctionnalités</a>
                <a class="font-medium text-slate-600 transition hover:text-primary" href="#excellence">Excellence</a>
            </div>

            <div class="flex items-center gap-3">
                <a href="#contact"
                    class="rounded-lg px-4 py-2 font-medium text-primary transition hover:bg-white/70">Connexion</a>
                <a href="#contact"
                    class="rounded-lg bg-primary px-5 py-2.5 font-semibold text-white shadow-sm transition hover:opacity-90">Demander
                    une démo</a>
            </div>
        </nav>
    </header>

    <main class="pt-24" id="top">
        <section class="relative overflow-hidden px-6 pb-28 pt-16 lg:px-8" id="produit">
            <div class="mx-auto flex max-w-screen-2xl flex-col items-center gap-16 lg:flex-row">
                <div class="z-10 flex-1 space-y-8">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">
                        Gestion officinale nouvelle génération
                    </div>

                    <h1 class="text-5xl font-extrabold leading-[1.1] tracking-tight text-primary lg:text-7xl">
                        La Révolution de la <span class="text-tertiary">Gestion Officinale</span>
                    </h1>

                    <p class="max-w-xl text-xl leading-relaxed text-muted">
                        DMS centralise toute votre activité pharmacie: ventes, stocks, assurances, inventaires,
                        sécurité et pilotage en temps réel.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <a href="#contact"
                            class="rounded-lg bg-primary px-8 py-4 text-lg font-bold text-white transition active:scale-95">Demander
                            une démo</a>
                        <a href="#fonctionnalites"
                            class="rounded-lg bg-[#d5e3fc] px-8 py-4 text-lg font-bold text-[#3a485b] transition active:scale-95">Voir
                            les fonctionnalités</a>
                    </div>
                </div>

                <div class="relative w-full flex-1">
                    <div class="overflow-hidden rounded-2xl bg-[#1a365d] p-2 shadow-2xl">
                        <img alt="Tableau de bord DMS" class="h-auto w-full rounded-xl object-cover"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAbRn7e_2le-iY9W2jghym3pmDIqQvXVJP8Het08d9zD6D_gYZmidReqUf2-3FVp6vdNp1CSYL5aw09aH-pj5G3YlCxQrz5lPN39Q36qhcyuJfEoeSyFRZ85VDp7uodra01Hu8B_JucfFFrUv8Vag5HimnMqohvhrxblTvistubIm-34fxi_ekUpwZbWfFUvTWQS3B9KhD3NsFKRoRcBBluTzBVn2V1r4nh3YdgX4-tbvd2PdUtBslDpgIc4SlLxvhIu-YpcT4yWU0p" />
                    </div>

                    <div class="absolute -bottom-6 -left-4 hidden rounded-xl bg-accent p-5 shadow-xl md:block">
                        <span class="block text-3xl font-black text-[#00201c]">100%</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-[#00201c]/80">Précision
                            inventaire</span>
                    </div>
                </div>
            </div>

            <div class="absolute right-0 top-0 -z-10 h-full w-1/3 bg-linear-to-l from-panel to-transparent"></div>
        </section>

        <section class="bg-surface py-24" id="fonctionnalites">
            <div class="mx-auto max-w-screen-2xl px-6 lg:px-8">
                <div class="mb-16 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <h2 class="mb-4 text-4xl font-bold tracking-tight text-primary">Fonctionnalités de DMS</h2>
                        <p class="text-lg text-muted">Une suite d'outils orchestrée pour automatiser chaque étape de
                            votre officine.</p>
                    </div>
                    <div class="font-bold italic tracking-tight text-tertiary">Compatible flux Pharma ML</div>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <article
                        class="group relative rounded-xl bg-white p-8 shadow-sm transition duration-300 hover:-translate-y-1">
                        <div class="status-pillar bg-primary"></div>
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-panel text-primary">
                            <span class="material-symbols-outlined">local_shipping</span>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-primary">Commande - livraison - validation</h3>
                        <p class="leading-relaxed text-muted">Suivi de bout en bout des flux d'approvisionnement avec
                            validation rapide.</p>
                    </article>

                    <article
                        class="group relative rounded-xl bg-white p-8 shadow-sm transition duration-300 hover:-translate-y-1">
                        <div class="status-pillar bg-accent"></div>
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-panel text-tertiary">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-primary">Facturation - recouvrement</h3>
                        <p class="leading-relaxed text-muted">Encaissement, recouvrement et gestion des avoirs
                            fournisseurs sur une même interface.</p>
                    </article>

                    <article
                        class="group relative rounded-xl bg-white p-8 shadow-sm transition duration-300 hover:-translate-y-1">
                        <div class="status-pillar bg-primary"></div>
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-panel text-primary">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-primary">Stock Min - Max</h3>
                        <p class="leading-relaxed text-muted">Niveaux cibles automatisés pour éviter ruptures et
                            surstocks.</p>
                    </article>

                    <article
                        class="group relative rounded-xl bg-white p-8 shadow-sm transition duration-300 hover:-translate-y-1">
                        <div class="status-pillar bg-accent"></div>
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-panel text-tertiary">
                            <span class="material-symbols-outlined">barcode_reader</span>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-primary">Contrôle des péremptions</h3>
                        <p class="leading-relaxed text-muted">Date de péremption sur code-barres et détection FEFO à la
                            vente.</p>
                    </article>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($dmsFeatures as $feature)
                        <div class="rounded-xl bg-panel p-5 text-sm font-medium text-[#2d476f] ring-1 ring-[#dce9ff]">
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-primary py-24 text-white" id="excellence">
            <div class="mx-auto max-w-screen-2xl px-6 lg:px-8">
                <div class="grid grid-cols-1 items-start gap-16 lg:grid-cols-2">
                    <div class="space-y-10">
                        <div>
                            <h2 class="text-4xl font-extrabold tracking-tight lg:text-5xl">Excellence opérationnelle
                            </h2>
                            <p class="mt-4 max-w-xl text-lg text-[#d6e3ff]">
                                Une infrastructure robuste pensée pour la continuité d'activité de la pharmacie,
                                l'auditabilité et la conformité.
                            </p>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="rounded-lg bg-[#1a365d] p-3 text-accent"><span
                                        class="material-symbols-outlined">shield_person</span></div>
                                <div>
                                    <h4 class="text-xl font-bold">200 points de contrôle d'accès</h4>
                                    <p class="text-[#d6e3ff]">Sécurité granulaire des profils et traçabilité complète
                                        des actions.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="rounded-lg bg-[#1a365d] p-3 text-accent"><span
                                        class="material-symbols-outlined">cloud_sync</span></div>
                                <div>
                                    <h4 class="text-xl font-bold">Sauvegarde à chaque arrêt de session</h4>
                                    <p class="text-[#d6e3ff]">Protection automatique des données sensibles de
                                        l'officine.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="rounded-lg bg-[#1a365d] p-3 text-accent"><span
                                        class="material-symbols-outlined">query_stats</span></div>
                                <div>
                                    <h4 class="text-xl font-bold">Espace statistique et états divers</h4>
                                    <p class="text-[#d6e3ff]">Pilotage par indicateurs, rapports métier et exports
                                        décisionnels.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4 pt-10">
                            <div class="aspect-square overflow-hidden rounded-2xl bg-[#1a365d]">
                                <img alt="Pharmacie moderne"
                                    class="h-full w-full object-cover opacity-70 transition duration-700 hover:scale-105"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCBhhj9qh3FUrY82oA3647u8sCnXeaadWj1qG3O9-TPZLtIz78wh5r2nUpeLWiPynkd37WcuT-pAG68ZfcpptK6Icxxx6SmW-fvUPa6pU2eVC6ffeTIWkvCcgvUl56YWWOoxVHov0XpOV93mCibf_01PgL_mzzTQwcdkfuN9uOq6izO13MfkwRi4DrwLQVIsrLvzsCRB-8xt-KkZ5YQdbG6_DPXTSPFLLhVQARO-OirxioUENXGMuRJI4-FaBI7XgfHfRQg8uGARwCw" />
                            </div>
                            <div class="rounded-2xl bg-[#003d36] p-6">
                                <div class="text-4xl font-black text-accent">99.9%</div>
                                <div class="text-xs font-bold uppercase tracking-widest text-accent/80">Disponibilité
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="h-56 rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                <div class="text-xs font-bold uppercase tracking-widest text-accent">Intégrations</div>
                                <div class="mt-3 text-2xl font-bold">AMO, Assurances, Pharma ML</div>
                            </div>
                            <div class="aspect-square overflow-hidden rounded-2xl bg-[#1a365d]">
                                <img alt="Infrastructure DMS"
                                    class="h-full w-full object-cover opacity-70 transition duration-700 hover:scale-105"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuC3aC3sQ2X5u5SWjpLd9nElJjp2fcSYpGK_lOW5ZzgSzye4zrR3IbCK14Vlq9kxAevd4pX4UhbCp7BOScK2W6DY1t1_jGVvvdN6eni8mJEhFpSjmnr6ZkMzNp4EYmA_8XUl4bS7PZlHD2ly0IpYkzi7RYnKjkKudyZa5WL9Kf8f-UWmdCMM_noIW8VjYwClr2buDq0n5C8fOPmyeqqSDtF77_i0qBTEukFbqMkuUtZIgKRlYPguwUuO9Zr7S7MY9XBB2IyuaVb0Ydne" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#eff4ff] py-24">
            <div class="mx-auto max-w-screen-2xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-14 lg:grid-cols-2">
                    <div>
                        <h2 class="text-4xl font-bold tracking-tight text-primary">Visualisez votre performance</h2>
                        <p class="mt-5 text-lg leading-relaxed text-muted">
                            DMS transforme vos données brutes en décisions opérationnelles. Inventaire tournant,
                            micro-inventaire progressif, alertes SMS, mobile paiement et archivage unifié.
                        </p>

                        <ul class="mt-8 space-y-3">
                            <li class="flex items-center gap-3 font-semibold text-primary"><span
                                    class="material-symbols-outlined text-tertiary">check_circle</span>Audit automatique
                                des écarts</li>
                            <li class="flex items-center gap-3 font-semibold text-primary"><span
                                    class="material-symbols-outlined text-tertiary">check_circle</span>Exports
                                multi-formats (PDF, Excel, Pharma ML)</li>
                            <li class="flex items-center gap-3 font-semibold text-primary"><span
                                    class="material-symbols-outlined text-tertiary">check_circle</span>Tableaux de bord
                                personnalisables</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/50">
                        <div class="mb-8 flex items-center justify-between">
                            <h3 class="text-2xl font-bold text-primary">Analyse dynamique</h3>
                            <span class="rounded bg-panel px-3 py-1 text-sm font-semibold text-[#3a485b]">Mensuel</span>
                        </div>

                        <div class="space-y-6">
                            <div class="border-l-4 border-accent pl-5">
                                <p class="text-xs font-bold uppercase tracking-widest text-muted">Inventaire tournant
                                </p>
                                <p class="text-4xl font-extrabold text-primary">84.2%</p>
                                <p class="text-xs font-bold text-tertiary">+12% vs mois dernier</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl bg-surface p-5 ring-1 ring-slate-200/40">
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted">Micro-inventaire
                                    </p>
                                    <p class="mt-2 text-2xl font-bold text-primary">Quotidien</p>
                                </div>
                                <div class="rounded-xl bg-surface p-5 ring-1 ring-slate-200/40">
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted">États</p>
                                    <p class="mt-2 text-2xl font-bold text-primary">+45</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-surface py-24" id="contact">
            <div class="mx-auto mb-12 max-w-4xl px-6 text-center lg:px-8">
                <h2 class="mb-4 text-4xl font-extrabold tracking-tight text-primary lg:text-5xl">Prêt à transformer
                    votre pharmacie ?</h2>
                <p class="text-xl text-muted">Passez à une gestion officinale intégrée, fiable et pilotée par la donnée.
                </p>
            </div>

            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <form method="POST" action="{{ route('company.presentation.contact') }}"
                    class="space-y-6 rounded-2xl border border-slate-200/40 bg-white p-8 shadow-xl lg:p-10">
                    @csrf

                    @if (session('status'))
                        <div
                            class="rounded-xl border border-accent/40 bg-[#edfff9] px-4 py-3 text-sm font-semibold text-tertiary">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold uppercase tracking-wider text-primary"
                                for="name">Prénom & nom</label>
                            <input class="w-full rounded-lg border-none bg-panel p-4 focus:ring-2 focus:ring-accent"
                                id="name" name="name" placeholder="Jean Dupont" type="text" value="{{ old('name') }}" />
                            @error('name')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold uppercase tracking-wider text-primary"
                                for="intent">Type de demande</label>
                            <select class="w-full rounded-lg border-none bg-panel p-4 focus:ring-2 focus:ring-accent"
                                id="intent" name="intent">
                                <option value="Implémentation DMS" @selected(old('intent') === 'Implémentation DMS')>
                                    Implémentation DMS</option>
                                <option value="Extranet Pharma ML" @selected(old('intent') === 'Extranet Pharma ML')>
                                    Extranet Pharma ML</option>
                                <option value="Gestion AMO et Assurances" @selected(old('intent') === 'Gestion AMO et Assurances')>Gestion AMO et assurances</option>
                                <option value="Audit opérationnel" @selected(old('intent') === 'Audit opérationnel')>Audit
                                    opérationnel</option>
                            </select>
                            @error('intent')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold uppercase tracking-wider text-primary"
                            for="email">Email professionnel</label>
                        <input class="w-full rounded-lg border-none bg-panel p-4 focus:ring-2 focus:ring-accent"
                            id="email" name="email" placeholder="contact@pharmacie.fr" type="email"
                            value="{{ old('email') }}" />
                        @error('email')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold uppercase tracking-wider text-primary"
                            for="message">Message (optionnel)</label>
                        <textarea class="w-full rounded-lg border-none bg-panel p-4 focus:ring-2 focus:ring-accent"
                            id="message" name="message" placeholder="Décrivez votre besoin"
                            rows="4">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        class="w-full rounded-lg bg-primary py-4 text-lg font-bold text-white transition hover:shadow-lg active:scale-[0.99]"
                        type="submit">
                        Envoyer ma demande de démo
                    </button>

                    <p class="text-center text-xs text-muted/80">En soumettant ce formulaire, vous acceptez notre
                        politique de confidentialité.</p>
                </form>
            </div>
        </section>
    </main>

    <footer class="mt-auto w-full border-t border-slate-200/60 bg-[#eff4ff]">
        <div
            class="mx-auto grid max-w-screen-2xl grid-cols-1 gap-8 px-8 py-14 text-sm text-slate-600 md:grid-cols-2 lg:px-12">
            <div class="space-y-4">
                <div class="text-lg font-semibold text-primary">{{ $companyName }}</div>
                <p class="max-w-md">Le système de gestion officinale nouvelle génération alliant robustesse métier et
                    expérience utilisateur claire.</p>
                <div>© {{ now()->year }} {{ $companyName }}</div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <div class="space-y-3">
                    <div class="text-xs font-bold uppercase tracking-widest text-primary">Contact</div>
                    <p>{{ $companyAddress }}</p>
                    <p>{{ $companyPhone }}</p>
                    <p>{{ $companyEmail }}</p>
                    <p>{{ $companyWebsite }}</p>
                </div>
                <div class="space-y-3">
                    <div class="text-xs font-bold uppercase tracking-widest text-primary">Navigation</div>
                    <a class="block hover:underline" href="#produit">Produit</a>
                    <a class="block hover:underline" href="#fonctionnalites">Fonctionnalités</a>
                    <a class="block hover:underline" href="#excellence">Excellence</a>
                    <a class="block hover:underline" href="#contact">Démo</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>