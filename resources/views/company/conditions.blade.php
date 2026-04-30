@extends('layouts.public')

@section('title', 'Conditions générales d\'utilisation — ' . $companyName)
@section('meta_description', 'Conditions générales d\'utilisation des services de ' . $companyName . '.')

@section('nav_links')
    <a href="{{ route('company.presentation') }}"
        class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Accueil</a>
    <a href="{{ route('company.presentation') }}#contact"
        class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Contact</a>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-6 py-20 lg:px-8">

        <div class="mb-12">
            <span
                class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Légal</span>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-[#002045]">Conditions générales d'utilisation</h1>
            <p class="mt-3 text-sm text-[#43474e]">Dernière mise à jour : {{ now()->format('d F Y') }}</p>
        </div>

        <div class="prose prose-slate max-w-none space-y-10 text-[#0b1c30]">

            <section>
                <h2 class="text-xl font-bold text-[#002045]">1. Objet</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation du site web et
                    des logiciels édités par <strong>{{ $companyName }}</strong>
                    @if($companyAddress), dont le siège social est situé au {{ $companyAddress }}@endif.
                    Tout accès au site implique l'acceptation sans réserve des présentes conditions.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">2. Accès aux services</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    L'accès aux services de {{ $companyName }} est conditionné à la conclusion d'un contrat de licence ou de
                    prestation de services. Les accès de démonstration sont soumis à une durée limitée et ne confèrent aucun
                    droit de propriété sur les logiciels ou données.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">3. Propriété intellectuelle</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    L'ensemble des éléments constituant le site et les logiciels (textes, graphiques, logotypes, icônes,
                    images, code source) sont la propriété exclusive de {{ $companyName }} ou de ses partenaires. Toute
                    reproduction, représentation, modification ou exploitation, même partielle, est strictement interdite
                    sans autorisation écrite préalable.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">4. Obligations de l'utilisateur</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">L'utilisateur s'engage à :</p>
                <ul class="mt-3 space-y-1 list-disc pl-6 text-[#43474e]">
                    <li>Utiliser les services conformément aux lois et règlements en vigueur</li>
                    <li>Ne pas tenter d'accéder à des données ou systèmes sans autorisation</li>
                    <li>Ne pas utiliser les services à des fins illicites ou préjudiciables à des tiers</li>
                    <li>Maintenir la confidentialité de ses identifiants d'accès</li>
                    <li>Signaler toute utilisation frauduleuse ou non autorisée à {{ $companyEmail ?: 'notre équipe' }}</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">5. Responsabilité</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    {{ $companyName }} s'engage à assurer la disponibilité de ses services avec le meilleur niveau de
                    qualité possible. Toutefois, {{ $companyName }} ne saurait être tenu responsable des interruptions de
                    service dues à des cas de force majeure, des opérations de maintenance, ou des défaillances des réseaux
                    de communication.
                </p>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    La responsabilité de {{ $companyName }} ne pourra être engagée pour tout dommage indirect résultant de
                    l'utilisation ou de l'impossibilité d'utilisation des services.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">6. Protection des données</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Le traitement des données personnelles est décrit dans notre <a
                        href="{{ route('company.confidentialite') }}" class="text-[#005048] underline">Politique de
                        confidentialité</a>. En utilisant nos services, vous acceptez les pratiques de traitement qui y sont
                    décrites.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">7. Droit applicable et juridiction</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Les présentes CGU sont soumises au droit malien. En cas de litige, les parties s'engagent à rechercher
                    une solution amiable avant tout recours judiciaire. À défaut, le litige sera soumis aux tribunaux
                    compétents de Bamako (République du Mali).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">8. Modification des CGU</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    {{ $companyName }} se réserve le droit de modifier les présentes CGU à tout moment. Les modifications
                    entrent en vigueur dès leur publication sur le site. L'utilisation continue des services après
                    publication vaut acceptation des nouvelles conditions.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">9. Contact</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Pour toute question relative aux présentes CGU :
                    @if($companyEmail)<br><a href="mailto:{{ $companyEmail }}"
                    class="text-[#005048] underline">{{ $companyEmail }}</a>@endif
                    @if($companyPhone)<br>{{ $companyPhone }}@endif
                    @if($companyAddress)<br>{{ $companyAddress }}@endif
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-2xl bg-[#eff4ff] p-8 text-center">
            <p class="text-sm font-medium text-[#43474e]">Une question sur nos conditions ?</p>
            <a href="{{ route('company.presentation') }}#contact"
                class="mt-4 inline-block rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                Nous contacter
            </a>
        </div>

    </div>
@endsection