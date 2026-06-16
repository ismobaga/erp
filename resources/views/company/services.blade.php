@extends('layouts.public')

@section('title', 'Services — CROMMIX MALI S.A.')
@section('meta_description', 'Services CROMMIX MALI S.A. : développement web, hébergement, logiciels métier, ERP, data, conseil IT et transformation digitale.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            <span class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Ce que nous faisons</span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Nos services</h1>
            <p class="mt-4 max-w-3xl text-lg leading-relaxed text-[#43474e]">
                Des services numériques orientés résultats pour structurer, sécuriser et accélérer les opérations de votre organisation.
            </p>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-20">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $services = [
                        ['title' => 'Développement web',        'desc' => 'Sites et applications web modernes, maintenables et performants.', 'icon' => '🌐', 'accent' => '#dce9ff', 'bar' => '#002045'],
                        ['title' => 'Hébergement & infra',      'desc' => 'Infrastructure stable avec supervision proactive et sauvegardes automatisées.', 'icon' => '🖥️', 'accent' => '#e0f2fe', 'bar' => '#0369a1'],
                        ['title' => 'Logiciels métier',         'desc' => 'Applications sur mesure conçues pour vos processus clés.', 'icon' => '⚙️', 'accent' => '#dcfce7', 'bar' => '#166534'],
                        ['title' => 'ERP & systèmes de gestion','desc' => 'Implémentation, adaptation et intégration de systèmes ERP complets.', 'icon' => '📊', 'accent' => '#fef3c7', 'bar' => '#92400e'],
                        ['title' => 'Data / ETL / reporting',   'desc' => 'Consolidation des données et tableaux de bord décisionnels en temps réel.', 'icon' => '📈', 'accent' => '#f0fdf4', 'bar' => '#70d8c8'],
                        ['title' => 'Conseil IT & formation',   'desc' => 'Accompagnement des équipes et montée en compétence technologique.', 'icon' => '🎓', 'accent' => '#fce7f3', 'bar' => '#9d174d'],
                        ['title' => 'Transformation digitale',  'desc' => 'Approche pragmatique et adaptée aux réalités des entreprises africaines.', 'icon' => '🌍', 'accent' => '#dce9ff', 'bar' => '#515f74'],
                    ];
                @endphp
                @foreach($services as $s)
                    <article class="relative overflow-hidden rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff] transition hover:shadow-md hover:-translate-y-0.5">
                        <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl" style="background:{{ $s['bar'] }}"></div>
                        <div class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl text-xl" style="background:{{ $s['accent'] }}">
                            {{ $s['icon'] }}
                        </div>
                        <h2 class="text-base font-bold text-[#002045]">{{ $s['title'] }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-[#43474e]">{{ $s['desc'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-12 rounded-2xl bg-[#002045] p-8 text-white">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-xl font-bold">Besoin d'un service sur mesure ?</h3>
                        <p class="mt-1 text-sm text-[#d6e3ff]">Parlons de votre projet et trouvons ensemble la meilleure approche.</p>
                    </div>
                    <a href="{{ route('company.contact') }}"
                        class="shrink-0 rounded-xl bg-[#8df5e4] px-6 py-3 text-sm font-bold text-[#002045] transition hover:opacity-90">
                        Nous contacter →
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
