@extends('layouts.public')

@section('title', 'À propos — CROMMIX MALI S.A.')
@section('meta_description', 'Découvrez CROMMIX MALI S.A., notre mission et notre positionnement pour la transformation numérique des organisations africaines.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <span
                class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">
                CROMMIX MALI S.A.
            </span>
            <h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Innovation numérique
                pour l'Afrique</h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-[#43474e]">
                CROMMIX MALI S.A. accompagne les PME, organisations et clients professionnels avec des solutions logicielles
                fiables, modernes et adaptées aux réalités africaines.
            </p>

            <div class="mt-12 grid grid-cols-2 gap-6 sm:grid-cols-3">
                @foreach([['2025', 'Fondée en'], ['1', 'Pays'], ['2', 'Logiciels disponibles']] as [$val, $label])
                    <div class="rounded-2xl bg-[#eff4ff] px-6 py-5">
                        <div class="text-3xl font-black text-[#002045]">{{ $val }}</div>
                        <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-[#43474e]">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-20">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <div class="mb-12 grid gap-6 md:grid-cols-3">
                <article class="relative overflow-hidden rounded-2xl bg-white p-7 shadow-sm">
                    <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-[#002045]"></div>
                    <div class="mb-4 text-2xl">🎯</div>
                    <h2 class="text-base font-bold text-[#002045]">Mission</h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Rendre la transformation digitale concrète, utile
                        et durable pour les entreprises africaines.</p>
                </article>
                <article class="relative overflow-hidden rounded-2xl bg-white p-7 shadow-sm">
                    <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-[#70d8c8]"></div>
                    <div class="mb-4 text-2xl">📍</div>
                    <h2 class="text-base font-bold text-[#002045]">Positionnement</h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Un partenaire technologique pratique pour les
                        opérations, la performance et la croissance.</p>
                </article>
                <article class="relative overflow-hidden rounded-2xl bg-white p-7 shadow-sm">
                    <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-[#515f74]"></div>
                    <div class="mb-4 text-2xl">🤝</div>
                    <h2 class="text-base font-bold text-[#002045]">Engagement</h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Qualité de service, sécurité des données et
                        accompagnement continu des équipes clientes.</p>
                </article>
            </div>

            <div class="rounded-2xl bg-[#002045] p-8 text-white lg:p-12">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-black tracking-tight">Pourquoi nous choisir ?</h2>
                    <p class="mt-4 text-sm leading-relaxed text-[#d6e3ff]">
                        Nous connaissons le terrain. Nos équipes sont basées en Afrique de l'Ouest et comprennent les
                        contraintes
                        réelles : connectivité variable, réglementations locales, pratiques commerciales spécifiques.
                        Nos solutions sont construites pour fonctionner dans ce contexte, pas simplement adaptées.
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach(['Support local & réactif', 'Tarification accessible', 'Formation incluse', 'Données hébergées localement'] as $point)
                            <div class="flex items-center gap-3 text-sm font-medium text-[#8df5e4]">
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#8df5e4]/20 text-xs">✓</span>
                                {{ $point }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection