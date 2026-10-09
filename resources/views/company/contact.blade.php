@extends('layouts.public')

@section('title', 'Contact — CROMMIX MALI S.A.')

@section('meta_description', 'Contactez CROMMIX MALI S.A. pour vos besoins en logiciels métier, ERP, conseil IT et transformation digitale.')

@section('content')
    <x-site.page-header eyebrow="Parlons-en" title="Contact" lead="Parlons de vos besoins et de votre feuille de route digitale." />

    <section class="site-container py-20">
        <div class="grid gap-10 lg:grid-cols-12">
            <aside class="space-y-4 lg:col-span-4">
                @foreach ([
                    ['map-pin', 'Adresse', $companyAddress ?: 'Bamako, Mali', null],
                    ['mail', 'E-mail', $companyEmail, $companyEmail ? 'mailto:'.$companyEmail : null],
                    ['phone', 'Téléphone', $companyPhone, $companyPhone ? 'tel:'.preg_replace('/[^0-9+]/', '', $companyPhone) : null],
                    ['link', 'Site web', $companyWebsite ?? null, $companyWebsite ?? null],
                ] as [$icon, $label, $value, $href])
                    @if (filled($value))
                        <div class="card flex items-start gap-4 p-5">
                            <span class="icon-tile h-10 w-10"><x-site.icon :name="$icon" class="h-4.5 w-4.5" /></span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</p>
                                @if ($href)
                                    <a href="{{ $href }}" class="mt-1 block break-words font-medium text-ink-900 transition hover:text-terra-700">{{ $value }}</a>
                                @else
                                    <p class="mt-1 break-words font-medium text-ink-900">{{ $value }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
                <div class="rounded-[1.25rem] border border-sand-200 bg-sand-100 p-6">
                    <p class="font-semibold text-ink-900">Plusieurs bureaux</p>
                    <p class="mt-1 text-sm text-ink-600">Bamako et Ouagadougou, pour toute l’Afrique de l’Ouest francophone.</p>
                    <a href="{{ route('company.bureaux') }}" class="link-arrow mt-3 text-sm">Voir nos bureaux <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
            </aside>

            <div class="lg:col-span-8">
                <div class="card p-7 sm:p-10">
                    <h2 class="display text-3xl">Écrivez-nous</h2>
                    <p class="mt-2 mb-8 text-sm text-ink-600">Décrivez votre besoin, nous revenons vers vous rapidement.</p>
                    <x-site.contact-form source="contact" default="Autre Enquête" :company-field="false" />
                </div>
            </div>
        </div>
    </section>
@endsection
