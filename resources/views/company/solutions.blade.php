@extends('layouts.public')

@section('title', 'Solutions — CROMMIX MALI S.A.')

@section('meta_description', 'Solutions CROMMIX MALI S.A. : DMS pour pharmacies, ERP/CGL pour la gestion d’entreprise, SiraLink et hébergement en préparation.')

@section('content')
    <x-site.page-header eyebrow="Produits" title="Solutions & produits"
        lead="Un portefeuille de solutions conçu pour les réalités opérationnelles des entreprises d’Afrique." />

    <section class="site-container py-20">
        <div class="grid gap-6 lg:grid-cols-2">
            <article class="card card-hover flex flex-col lg:row-span-2 lg:p-10">
                <div class="flex items-center justify-between">
                    <span class="badge badge-available"><span class="h-1.5 w-1.5 rounded-full bg-kola-600"></span> Disponible</span>
                    <span class="icon-tile"><x-site.icon name="beaker" class="h-5 w-5" /></span>
                </div>
                <h2 class="display mt-8 text-4xl">DMS</h2>
                <p class="mt-1 text-sm font-medium text-ink-500">Drugstore Management System</p>
                <p class="mt-5 leading-relaxed text-ink-600">Outil moderne de gestion des pharmacies : stock, commandes, facturation et suivi opérationnel.</p>
                <ul class="mt-6 grid gap-2 text-sm text-ink-700 sm:grid-cols-2">
                    @foreach (['Commandes & stock', 'Péremptions', 'Facturation', 'Assurances mutuelles'] as $item)
                        <li class="flex items-center gap-2"><x-site.icon name="check" class="h-4 w-4 text-kola-600" /> {{ $item }}</li>
                    @endforeach
                </ul>
                <div class="mt-auto flex flex-wrap gap-2 pt-10">
                    <a href="{{ route('dms.presentation') }}" class="btn btn-primary btn-sm">Voir la présentation</a>
                    <a href="{{ route('company.presentation', ['intent' => 'Demande démo DMS']) }}#contact" class="btn btn-outline btn-sm">Demander une démo</a>
                </div>
            </article>

            <article class="card card-hover">
                <div class="flex items-center justify-between">
                    <span class="badge badge-soon">Prioritaire</span>
                    <span class="icon-tile"><x-site.icon name="squares" class="h-5 w-5" /></span>
                </div>
                <h2 class="display mt-6 text-3xl">CROMMIX ERP / CGL</h2>
                <p class="mt-4 text-sm leading-relaxed text-ink-600">Pilotage financier et opérationnel pour centraliser les processus d’entreprise.</p>
                <a href="{{ route('company.presentation', ['intent' => 'Implémentation ERP']) }}#contact" class="link-arrow mt-5 text-sm">Être recontacté <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </article>

            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ([
                    ['truck', 'SiraLink Fleet Tracking', 'Suivi et supervision de flotte, avec roadmap produit progressive.'],
                    ['cloud', 'Services d’hébergement', 'Offres d’hébergement et d’exploitation managée selon les besoins clients.'],
                ] as [$icon, $title, $text])
                    <article class="rounded-[1.25rem] border border-dashed border-sand-300 bg-sand-100/60 p-7">
                        <div class="flex items-center justify-between">
                            <span class="badge badge-muted">Bientôt</span>
                            <span class="text-ink-500"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                        </div>
                        <h2 class="mt-6 text-lg font-semibold text-ink-800">{{ $title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-site.cta title="Une solution adaptée à votre activité ?" text="Présentez-nous vos besoins : nous vous orientons vers le bon outil, ou nous le construisons." />
@endsection
