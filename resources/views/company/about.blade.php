@extends('layouts.public')

@section('title', 'À propos — CROMMIX MALI S.A.')

@section('meta_description', 'Découvrez CROMMIX MALI S.A., notre mission et notre positionnement pour la transformation numérique des organisations africaines.')

@section('content')
    <x-site.page-header eyebrow="À propos" title="Innovation numérique pour l’Afrique."
        lead="CROMMIX MALI S.A. accompagne les PME, organisations et clients professionnels avec des solutions logicielles fiables, modernes et adaptées aux réalités africaines." />

    <section class="site-container py-20">
        <dl class="grid gap-px overflow-hidden rounded-[1.5rem] border border-sand-200 bg-sand-200 sm:grid-cols-3">
            @foreach ([['2025', 'Fondée en'], ['1', 'Pays'], ['2', 'Logiciels disponibles']] as [$value, $label])
                <div class="bg-white px-8 py-8">
                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">{{ $label }}</dt>
                    <dd class="display mt-3 text-4xl">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-20 grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p class="eyebrow">Ce qui nous guide</p>
                <h2 class="display mt-5 text-4xl">Utile, concret, durable.</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-3 lg:col-span-8">
                @foreach ([
                    ['target', 'Mission', 'Rendre la transformation digitale concrète, utile et durable pour les entreprises africaines.'],
                    ['map-pin', 'Positionnement', 'Un partenaire technologique pratique pour les opérations, la performance et la croissance.'],
                    ['users', 'Engagement', 'Qualité de service, sécurité des données et accompagnement continu des équipes clientes.'],
                ] as [$icon, $title, $text])
                    <article class="card">
                        <span class="icon-tile"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                        <h3 class="mt-6 text-lg font-semibold text-ink-900">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-bogolan-dark py-24 text-sand-50">
        <div class="site-container grid gap-14 lg:grid-cols-2">
            <div>
                <p class="eyebrow eyebrow-light">Pourquoi nous choisir</p>
                <h2 class="display mt-5 text-4xl text-sand-50 sm:text-5xl">Nous connaissons le terrain.</h2>
            </div>
            <div>
                <p class="text-lg leading-relaxed text-sand-200/85">
                    Nos équipes sont basées en Afrique de l’Ouest et comprennent les contraintes réelles : connectivité variable,
                    réglementations locales, pratiques commerciales spécifiques. Nos solutions sont construites pour fonctionner
                    dans ce contexte, pas simplement adaptées.
                </p>
                <ul class="mt-10 grid gap-4 sm:grid-cols-2">
                    @foreach (['Support local & réactif', 'Tarification accessible', 'Formation incluse', 'Données hébergées localement'] as $point)
                        <li class="flex items-center gap-3 border-t border-sand-50/15 pt-4 font-medium text-sand-50">
                            <x-site.icon name="check" class="h-5 w-5 text-terra-400" /> {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <x-site.cta title="Construisons ensemble votre feuille de route digitale."
        text="Un premier échange suffit pour identifier les leviers les plus utiles pour votre organisation." />
@endsection
