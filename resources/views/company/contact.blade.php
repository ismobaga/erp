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
                    <p class="mt-1 text-sm text-[#0b1c30]">{{ $companyEmail }}</p>
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

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-[#70d8c8]/40 bg-white px-4 py-3 text-sm font-semibold text-[#005048]">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('company.presentation.contact') }}"
                class="mt-8 grid grid-cols-1 gap-4 rounded-2xl bg-white p-6 shadow-sm">
                @csrf
                <input type="hidden" name="source" value="contact">

                <div>
                    <label for="name" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Nom complet</label>
                    <input id="name" name="name" value="{{ old('name') }}" type="text"
                        class="mt-1 w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3 outline-none transition focus:border-[#002045]/20">
                    @error('name')
                        <p class="mt-1 text-sm text-[#ba1a1a]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">E-mail professionnel</label>
                    <input id="email" name="email" value="{{ old('email') }}" type="email"
                        class="mt-1 w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3 outline-none transition focus:border-[#002045]/20">
                    @error('email')
                        <p class="mt-1 text-sm text-[#ba1a1a]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="intent" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Sujet</label>
                    <select id="intent" name="intent"
                        class="mt-1 w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3 outline-none transition focus:border-[#002045]/20">
                        <option value="Consultation Digitale" @selected(old('intent') === 'Consultation Digitale')>Consultation digitale</option>
                        <option value="Demande démo DMS" @selected(old('intent') === 'Demande démo DMS')>Demande démo DMS</option>
                        <option value="Implémentation ERP" @selected(old('intent') === 'Implémentation ERP')>Implémentation ERP</option>
                        <option value="Autre Enquête" @selected(old('intent', 'Autre Enquête') === 'Autre Enquête')>Autre enquête</option>
                    </select>
                    @error('intent')
                        <p class="mt-1 text-sm text-[#ba1a1a]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="text-xs font-bold uppercase tracking-widest text-[#43474e]">Message</label>
                    <textarea id="message" name="message" rows="4"
                        class="mt-1 w-full rounded-xl border border-[#c4c6cf]/40 bg-[#f8f9ff] p-3 outline-none transition focus:border-[#002045]/20">{{ old('message') }}</textarea>
                </div>

                <button type="submit"
                    class="rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                    Envoyer la demande
                </button>
            </form>
        </div>
    </section>
@endsection
