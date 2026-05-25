@extends('layouts.public')

@section('title', 'Solutions — CROMMIX MALI S.A.')
@section('meta_description', 'Solutions CROMMIX MALI S.A. : DMS pour pharmacies, ERP/CGL pour la gestion d’entreprise, SiraLink et hébergement en préparation.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            <h1 class="text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Solutions & produits</h1>
            <p class="mt-4 max-w-3xl text-lg leading-relaxed text-[#43474e]">
                Un portefeuille de solutions conçu pour les réalités opérationnelles des entreprises d’Afrique.
            </p>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-20">
        <div class="mx-auto grid max-w-6xl gap-6 px-6 md:grid-cols-2 lg:px-8">
            <article class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff]">
                <span class="rounded-lg bg-[#8df5e4]/30 px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#005048]">Disponible</span>
                <h2 class="mt-4 text-2xl font-bold text-[#002045]">DMS — Drugstore Management System</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Outil moderne de gestion des pharmacies : stock, commandes, facturation et suivi opérationnel.</p>
                <a href="{{ route('dms.presentation') }}" class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-[#002045]">Voir la présentation →</a>
            </article>

            <article class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff]">
                <span class="rounded-lg bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#2d476f]">Prioritaire</span>
                <h2 class="mt-4 text-2xl font-bold text-[#002045]">CROMMIX ERP / CGL</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Pilotage financier et opérationnel pour centraliser les processus d’entreprise.</p>
            </article>

            <article class="rounded-2xl border-2 border-dashed border-[#c4c6cf]/40 bg-[#f8f9ff] p-7">
                <span class="rounded-lg bg-white px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#43474e]">Bientôt</span>
                <h2 class="mt-4 text-2xl font-bold text-[#43474e]">SiraLink Fleet Tracking</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Suivi et supervision de flotte, avec roadmap produit progressive.</p>
            </article>

            <article class="rounded-2xl border-2 border-dashed border-[#c4c6cf]/40 bg-[#f8f9ff] p-7">
                <span class="rounded-lg bg-white px-3 py-1 text-xs font-bold uppercase tracking-widest text-[#43474e]">Bientôt</span>
                <h2 class="mt-4 text-2xl font-bold text-[#43474e]">Services d’hébergement</h2>
                <p class="mt-3 text-sm leading-relaxed text-[#43474e]">Offres d’hébergement et d’exploitation managée selon les besoins clients.</p>
            </article>
        </div>
    </section>
@endsection
