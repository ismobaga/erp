@extends('layouts.public')

@section('title', 'Contact — CROMMIX MALI S.A.')
@section('meta_description', 'Contactez CROMMIX MALI S.A. pour vos besoins en logiciels métier, ERP, conseil IT et transformation digitale.')

@section('content')
    <section class="bg-[#f8f9ff] py-24">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <span class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Parlons-nous</span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Contact</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-[#43474e]">Parlons de vos besoins et de votre feuille de route digitale.</p>
        </div>
    </section>

    <section class="bg-[#eff4ff] py-16">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-5">
                {{-- Contact info sidebar --}}
                <div class="lg:col-span-2 space-y-4">
                    @foreach([
                        ['📍', 'Adresse', $companyAddress ?: 'Mali'],
                        ['✉️', 'Email',   $companyEmail],
                        ['☎️', 'Téléphone', $companyPhone ?: 'N/A'],
                        ['🔗', 'Site web',  $companyWebsite ?? ''],
                    ] as [$icon, $label, $value])
                        @if($value)
                        <div class="flex gap-4 rounded-2xl bg-white p-5 shadow-sm">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#eff4ff] text-lg">{{ $icon }}</div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-widest text-[#43474e]">{{ $label }}</p>
                                <p class="mt-1 text-sm font-medium text-[#0b1c30] wrap-break-word">{{ $value }}</p>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>

                {{-- Contact form --}}
                <div class="lg:col-span-3">
                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-[#70d8c8]/40 bg-white px-4 py-3 text-sm font-semibold text-[#005048]">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('company.presentation.contact') }}"
                        class="grid grid-cols-1 gap-5 rounded-2xl bg-white p-7 shadow-sm">
                        @csrf
                        <input type="hidden" name="source" value="contact">

                        <div class="space-y-1">
                            <label for="name" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Nom complet</label>
                            <input id="name" name="name" value="{{ old('name') }}" type="text" placeholder="Jean Dupont"
                                class="w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3.5 outline-none transition focus:border-[#002045]/30 focus:ring-2 focus:ring-[#002045]/10">
                            @error('name')
                                <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="email" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">E-mail professionnel</label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email" placeholder="j.dupont@entreprise.com"
                                class="w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3.5 outline-none transition focus:border-[#002045]/30 focus:ring-2 focus:ring-[#002045]/10">
                            @error('email')
                                <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="intent" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Sujet</label>
                            <select id="intent" name="intent"
                                class="w-full appearance-none rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3.5 outline-none transition focus:border-[#002045]/30 focus:ring-2 focus:ring-[#002045]/10">
                                <option value="Consultation Digitale" @selected(old('intent') === 'Consultation Digitale')>Consultation digitale</option>
                                <option value="Demande démo DMS" @selected(old('intent') === 'Demande démo DMS')>Demande démo DMS</option>
                                <option value="Implémentation ERP" @selected(old('intent') === 'Implémentation ERP')>Implémentation ERP</option>
                                <option value="Autre Enquête" @selected(old('intent', 'Autre Enquête') === 'Autre Enquête')>Autre enquête</option>
                            </select>
                            @error('intent')
                                <p class="text-sm text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="message" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Message</label>
                            <textarea id="message" name="message" rows="4" placeholder="Décrivez brièvement votre besoin..."
                                class="w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3.5 outline-none transition focus:border-[#002045]/30 focus:ring-2 focus:ring-[#002045]/10">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit"
                            class="rounded-xl bg-[#002045] py-4 font-bold text-white shadow-lg shadow-[#002045]/10 transition hover:opacity-90">
                            Envoyer la demande
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
