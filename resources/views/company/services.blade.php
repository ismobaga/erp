@extends('layouts.public')

@section('title', 'Services — CROMMIX MALI S.A.')

@section('meta_description', 'Services CROMMIX MALI S.A. : développement web, hébergement, logiciels métier, ERP, data, conseil IT et transformation digitale.')

@section('content')
    <x-site.page-header eyebrow="Ce que nous faisons" title="Nos services"
        lead="Des services numériques orientés résultats pour structurer, sécuriser et accélérer les opérations de votre organisation." />

    @php
        $services = [
            ['code', 'Développement web', 'Sites et applications web modernes, maintenables et performants.'],
            ['server', 'Hébergement & infra', 'Infrastructure stable avec supervision proactive et sauvegardes automatisées.'],
            ['adjustments', 'Logiciels métier', 'Applications sur mesure conçues pour vos processus clés.'],
            ['squares', 'ERP & systèmes de gestion', 'Implémentation, adaptation et intégration de systèmes ERP complets.'],
            ['chart-bar', 'Data / ETL / reporting', 'Consolidation des données et tableaux de bord décisionnels en temps réel.'],
            ['academic-cap', 'Conseil IT & formation', 'Accompagnement des équipes et montée en compétence technologique.'],
            ['globe', 'Transformation digitale', 'Approche pragmatique et adaptée aux réalités des entreprises africaines.'],
        ];
    @endphp

    <section class="site-container py-20">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $i => [$icon, $title, $text])
                <article class="card card-hover flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="icon-tile"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                        <span class="font-display text-2xl font-semibold text-sand-300">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h2 class="mt-8 text-xl font-semibold text-ink-900">{{ $title }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                </article>
            @endforeach

            <article class="flex flex-col justify-between rounded-[1.25rem] border border-sand-200 bg-sand-100 p-7">
                <p class="display text-2xl">Un besoin qui ne rentre dans aucune case ?</p>
                <a href="{{ route('company.contact') }}" class="link-arrow mt-6">Parlons-en <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </article>
        </div>
    </section>

    <x-site.cta title="Besoin d’un service sur mesure ?" text="Parlons de votre projet et trouvons ensemble la meilleure approche." />
@endsection
