@extends('layouts.public')

@section('title', 'À propos — CROMMIX MALI S.A.')
@section('meta_description', 'Découvrez CROMMIX MALI S.A., notre mission et notre positionnement pour la transformation numérique des organisations africaines.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <span class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">
                CROMMIX MALI S.A.
            </span>
            <h1 class="mt-6 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Innovation numérique pour l’Afrique</h1>
            <p class="mt-6 text-lg leading-relaxed text-[#43474e]">
                CROMMIX MALI S.A. accompagne les PME, organisations et clients professionnels avec des solutions logicielles
                fiables, modernes et adaptées aux réalités africaines.
            </p>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-20">
        <div class="mx-auto grid max-w-5xl gap-6 px-6 md:grid-cols-3 lg:px-8">
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#002045]">Mission</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Rendre la transformation digitale concrète, utile et durable pour les entreprises africaines.</p>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#002045]">Positionnement</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Un partenaire technologique pratique pour les opérations, la performance et la croissance.</p>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#002045]">Engagement</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Qualité de service, sécurité des données et accompagnement continu des équipes clientes.</p>
            </article>
        </div>
    </section>
@endsection
