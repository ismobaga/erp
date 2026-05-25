@extends('layouts.public')

@section('title', 'Contact — CROMMIX MALI S.A.')
@section('meta_description', 'Contactez CROMMIX MALI S.A. pour vos besoins en logiciels métier, ERP, conseil IT et transformation digitale.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <h1 class="text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Contact</h1>
            <p class="mt-4 max-w-3xl text-lg leading-relaxed text-[#43474e]">Parlons de vos besoins et de votre feuille de route digitale.</p>

            <div class="mt-10 grid gap-4 rounded-2xl bg-white p-6 shadow-sm md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#43474e]">Email</p>
                    <p class="mt-1 text-sm text-[#0b1c30]">{{ $companyEmail ?: 'contact@crommix.com' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#43474e]">Téléphone</p>
                    <p class="mt-1 text-sm text-[#0b1c30]">{{ $companyPhone ?: 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#43474e]">Adresse</p>
                    <p class="mt-1 text-sm text-[#0b1c30]">{{ $companyAddress ?: 'Mali' }}</p>
                </div>
            </div>

            <a href="{{ route('company.presentation') }}#contact"
                class="mt-8 inline-flex items-center gap-2 rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                Envoyer une demande
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
@endsection
