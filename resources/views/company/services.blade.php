@extends('layouts.public')

@section('title', 'Services — CROMMIX MALI S.A.')
@section('meta_description', 'Services CROMMIX MALI S.A. : développement web, hébergement, logiciels métier, ERP, data, conseil IT et transformation digitale.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            <h1 class="text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Nos services</h1>
            <p class="mt-4 max-w-3xl text-lg leading-relaxed text-[#43474e]">
                Des services numériques orientés résultats pour structurer, sécuriser et accélérer les opérations de votre organisation.
            </p>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-20">
        <div class="mx-auto grid max-w-6xl gap-6 px-6 md:grid-cols-2 lg:grid-cols-3 lg:px-8">
            @foreach ([['Développement web', 'Sites et applications web modernes et maintenables.'], ['Hébergement web & managé', 'Infrastructure stable, supervision et sauvegardes.'], ['Développement logiciel', 'Applications métier sur mesure pour vos processus clés.'], ['ERP & systèmes de gestion', 'Implémentation et adaptation de systèmes ERP.'], ['Data / ETL / reporting', 'Consolidation des données et tableaux de bord décisionnels.'], ['Conseil IT & formation', 'Accompagnement des équipes et montée en compétence.'], ['Transformation digitale', 'Approche pragmatique pour les entreprises africaines.']] as [$title, $description])
                <article class="rounded-2xl bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#002045]">{{ $title }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#43474e]">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
