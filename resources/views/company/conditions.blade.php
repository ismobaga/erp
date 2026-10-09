@extends('layouts.public')

@section('title', 'Conditions générales d\'utilisation — ' . $companyName)
@section('meta_description', 'Conditions générales d\'utilisation des services de ' . $companyName . '.')

@section('content')
    <x-site.page-header eyebrow="Informations légales" title="Conditions générales d'utilisation" />

    <div class="site-container py-16 lg:py-20">
        <div class="mx-auto max-w-3xl">
        <p class="mb-10 text-sm text-ink-500">Dernière mise à jour : {{ now()->translatedFormat('d F Y') }}</p>

        <div class="legal-content">

            <section>
                <h2>1. Objet</h2>
                <p>
                    Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation du site web et
                    des logiciels édités par <strong>{{ $companyName }}</strong>
                    @if($companyAddress), dont le siège social est situé au {{ $companyAddress }}@endif.
                    Tout accès au site implique l'acceptation sans réserve des présentes conditions.
                </p>
            </section>

            <section>
                <h2>2. Accès aux services</h2>
                <p>
                    L'accès aux services de {{ $companyName }} est conditionné à la conclusion d'un contrat de licence ou de
                    prestation de services. Les accès de démonstration sont soumis à une durée limitée et ne confèrent aucun
                    droit de propriété sur les logiciels ou données.
                </p>
            </section>

            <section>
                <h2>3. Propriété intellectuelle</h2>
                <p>
                    L'ensemble des éléments constituant le site et les logiciels (textes, graphiques, logotypes, icônes,
                    images, code source) sont la propriété exclusive de {{ $companyName }} ou de ses partenaires. Toute
                    reproduction, représentation, modification ou exploitation, même partielle, est strictement interdite
                    sans autorisation écrite préalable.
                </p>
            </section>

            <section>
                <h2>4. Obligations de l'utilisateur</h2>
                <p>L'utilisateur s'engage à :</p>
                <ul>
                    <li>Utiliser les services conformément aux lois et règlements en vigueur</li>
                    <li>Ne pas tenter d'accéder à des données ou systèmes sans autorisation</li>
                    <li>Ne pas utiliser les services à des fins illicites ou préjudiciables à des tiers</li>
                    <li>Maintenir la confidentialité de ses identifiants d'accès</li>
                    <li>Signaler toute utilisation frauduleuse ou non autorisée à {{ $companyEmail ?: 'notre équipe' }}</li>
                </ul>
            </section>

            <section>
                <h2>5. Responsabilité</h2>
                <p>
                    {{ $companyName }} s'engage à assurer la disponibilité de ses services avec le meilleur niveau de
                    qualité possible. Toutefois, {{ $companyName }} ne saurait être tenu responsable des interruptions de
                    service dues à des cas de force majeure, des opérations de maintenance, ou des défaillances des réseaux
                    de communication.
                </p>
                <p>
                    La responsabilité de {{ $companyName }} ne pourra être engagée pour tout dommage indirect résultant de
                    l'utilisation ou de l'impossibilité d'utilisation des services.
                </p>
            </section>

            <section>
                <h2>6. Protection des données</h2>
                <p>
                    Le traitement des données personnelles est décrit dans notre <a
                        href="{{ route('company.confidentialite') }}">Politique de
                        confidentialité</a>. En utilisant nos services, vous acceptez les pratiques de traitement qui y sont
                    décrites.
                </p>
            </section>

            <section>
                <h2>7. Droit applicable et juridiction</h2>
                <p>
                    Les présentes CGU sont soumises au droit malien. En cas de litige, les parties s'engagent à rechercher
                    une solution amiable avant tout recours judiciaire. À défaut, le litige sera soumis aux tribunaux
                    compétents de Bamako (République du Mali).
                </p>
            </section>

            <section>
                <h2>8. Modification des CGU</h2>
                <p>
                    {{ $companyName }} se réserve le droit de modifier les présentes CGU à tout moment. Les modifications
                    entrent en vigueur dès leur publication sur le site. L'utilisation continue des services après
                    publication vaut acceptation des nouvelles conditions.
                </p>
            </section>

            <section>
                <h2>9. Contact</h2>
                <p>
                    Pour toute question relative aux présentes CGU :
                    @if($companyEmail)<br><a href="mailto:{{ $companyEmail }}"
                   >{{ $companyEmail }}</a>@endif
                    @if($companyPhone)<br>{{ $companyPhone }}@endif
                    @if($companyAddress)<br>{{ $companyAddress }}@endif
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-[1.5rem] border border-sand-200 bg-sand-100 p-8 text-center">
            <p class="text-sm font-medium text-ink-700">Une question sur nos conditions ?</p>
            <a href="{{ route('company.presentation') }}#contact"
                class="btn btn-primary mt-4">
                Nous contacter
            </a>
        </div>

    </div>
    </div>
@endsection