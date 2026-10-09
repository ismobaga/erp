@extends('layouts.public')

@section('title', 'Nos bureaux — ' . $companyName)

@section('meta_description', 'Retrouvez les bureaux et coordonnées de ' . $companyName . ' en Afrique de l\'Ouest.')

@section('content')
    <x-site.page-header eyebrow="Présence régionale" title="Nos bureaux"
        lead="Au cœur de l’Afrique de l’Ouest, nos équipes vous accompagnent depuis deux pays pour servir toute la région." />

    <section class="site-container py-20">
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Siège : Mali --}}
            <article class="card flex flex-col p-8 lg:p-10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-terra-700">Siège social — Mali</p>
                        <h2 class="display mt-3 text-3xl">{{ $companyName }}</h2>
                    </div>
                    <span class="badge badge-available shrink-0">Siège</span>
                </div>
                <ul class="mt-8 space-y-4 text-sm text-ink-800">
                    <li class="flex gap-3">
                        <x-site.icon name="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                        <span>{!! $companyAddress ? e($companyAddress) : 'Bamako (République du Mali)<br>Bacodjicoroni Golf, Rue 661 Porte 343' !!}</span>
                    </li>
                    <li class="flex gap-3">
                        <x-site.icon name="phone" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                        @php $phone = $companyPhone ?: '+223 83 45 08 83'; @endphp
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="transition hover:text-terra-700">{{ $phone }}</a>
                    </li>
                    @if ($companyEmail)
                        <li class="flex gap-3">
                            <x-site.icon name="mail" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                            <a href="mailto:{{ $companyEmail }}" class="transition hover:text-terra-700">{{ $companyEmail }}</a>
                        </li>
                    @endif
                    @if ($companyWebsite)
                        <li class="flex gap-3">
                            <x-site.icon name="link" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                            <a href="{{ $companyWebsite }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-terra-700">{{ $companyWebsite }}</a>
                        </li>
                    @endif
                </ul>
                <p class="mt-auto pt-8 text-sm text-ink-500">ERP · Web · Conseil</p>
            </article>

            {{-- Partenaire : Burkina Faso --}}
            <article class="card flex flex-col p-8 lg:p-10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-terra-700">Bureau partenaire — Burkina Faso</p>
                        <h2 class="display mt-3 text-3xl">Crommix</h2>
                    </div>
                    <span class="badge badge-muted shrink-0">Partenaire</span>
                </div>
                <ul class="mt-8 space-y-4 text-sm text-ink-800">
                    <li class="flex gap-3">
                        <x-site.icon name="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                        <span>Ouagadougou (Burkina Faso)</span>
                    </li>
                    <li class="flex gap-3">
                        <x-site.icon name="phone" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                        <a href="tel:+22625502000" class="transition hover:text-terra-700">+226 25 50 20 00</a>
                    </li>
                    <li class="flex gap-3">
                        <x-site.icon name="link" class="mt-0.5 h-5 w-5 shrink-0 text-terra-700" />
                        <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer" class="transition hover:text-terra-700">crommix.com</a>
                    </li>
                </ul>
                <p class="mt-auto pt-8 text-sm text-ink-500">DMS · Logiciels métier</p>
            </article>
        </div>
    </section>

    <section class="bg-bogolan-dark py-24 text-sand-50">
        <div class="site-container grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="eyebrow eyebrow-light">Zone de couverture</p>
                <h2 class="display mt-5 text-4xl text-sand-50 sm:text-5xl">Toute l’Afrique de l’Ouest francophone.</h2>
                <p class="mt-6 max-w-lg leading-relaxed text-sand-200/80">Nos solutions sont déployées et supportées dans toute l’Afrique de l’Ouest francophone, avec une expertise particulière sur :</p>
            </div>
            <ul class="grid grid-cols-2 gap-x-8 gap-y-5">
                @foreach (['Mali', 'Burkina Faso', 'Côte d’Ivoire', 'Sénégal', 'Niger', 'Guinée', 'Bénin'] as $country)
                    <li class="flex items-center gap-3 border-t border-sand-50/15 pt-4 text-lg font-medium text-sand-50">
                        <x-site.icon name="check" class="h-5 w-5 text-terra-400" /> {{ $country }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <x-site.cta title="Vous souhaitez nous rendre visite ?" text="Ou discuter de votre projet : écrivez-nous, nous organisons la rencontre." label="Prendre contact" />
@endsection
