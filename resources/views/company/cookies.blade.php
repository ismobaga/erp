@extends('layouts.public')

@section('title', 'Politique en matière de cookies — ' . $companyName)
@section('meta_description', 'Comment ' . $companyName . ' utilise les cookies et technologies similaires sur son site.')

@section('content')
    <x-site.page-header eyebrow="Informations légales" title="Politique en matière de cookies" />

    <div class="site-container py-16 lg:py-20">
        <div class="mx-auto max-w-3xl">
        <p class="mb-10 text-sm text-ink-500">Dernière mise à jour : {{ now()->translatedFormat('d F Y') }}</p>

        <div class="legal-content">

            <section>
                <h2>1. Qu'est-ce qu'un cookie ?</h2>
                <p>
                    Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur, tablette, mobile) lors de la
                    visite d'un site web. Il permet au site de mémoriser vos préférences et de vous offrir une expérience
                    personnalisée lors de vos visites ultérieures.
                </p>
            </section>

            <section>
                <h2>2. Cookies utilisés sur ce site</h2>
                <p>Le site de {{ $companyName }} utilise les catégories de
                    cookies suivantes :</p>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-sand-200">
                    <table class="w-full text-sm">
                        <thead class="bg-sand-100">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-ink-900">Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-ink-900">Finalité</th>
                                <th class="px-5 py-3 text-left font-semibold text-ink-900">Durée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sand-200">
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-ink-900">Essentiels</td>
                                <td class="px-5 py-4 text-ink-700">Session, sécurité CSRF, préférences de langue</td>
                                <td class="px-5 py-4 text-ink-700">Session / 1 an</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-ink-900">Fonctionnels</td>
                                <td class="px-5 py-4 text-ink-700">Mémorisation de vos préférences d'affichage</td>
                                <td class="px-5 py-4 text-ink-700">6 mois</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-ink-900">Analytiques</td>
                                <td class="px-5 py-4 text-ink-700">Mesure d'audience anonymisée (pages vues, durée)</td>
                                <td class="px-5 py-4 text-ink-700">13 mois</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2>3. Cookies strictement nécessaires</h2>
                <p>
                    Ces cookies sont indispensables au fonctionnement du site. Ils permettent notamment d'assurer la
                    sécurité des formulaires (protection CSRF) et de maintenir votre session lors de votre navigation. Ils
                    ne peuvent pas être désactivés.
                </p>
            </section>

            <section>
                <h2>4. Cookies analytiques</h2>
                <p>
                    Ces cookies nous aident à comprendre comment les visiteurs utilisent notre site (pages les plus
                    consultées, parcours de navigation, taux de rebond). Ces données sont collectées de manière anonyme et
                    ne permettent pas de vous identifier personnellement.
                </p>
            </section>

            <section>
                <h2>5. Gestion de vos préférences</h2>
                <p>
                    Vous pouvez à tout moment configurer votre navigateur pour refuser ou supprimer les cookies. Voici
                    comment procéder selon votre navigateur :
                </p>
                <ul>
                    <li><strong>Chrome</strong> : Paramètres → Confidentialité et sécurité → Cookies</li>
                    <li><strong>Firefox</strong> : Paramètres → Vie privée et sécurité → Cookies</li>
                    <li><strong>Safari</strong> : Préférences → Confidentialité → Cookies</li>
                    <li><strong>Edge</strong> : Paramètres → Cookies et données de site</li>
                </ul>
                <p>
                    Notez que la désactivation de certains cookies peut affecter le fonctionnement et la sécurité du site.
                </p>
            </section>

            <section>
                <h2>6. Contact</h2>
                <p>
                    Pour toute question sur notre utilisation des cookies, contactez-nous à
                    @if($companyEmail)<a href="mailto:{{ $companyEmail }}"
                       >{{ $companyEmail }}</a>
                    @else
                        notre équipe.
                    @endif
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-[1.5rem] border border-sand-200 bg-sand-100 p-8 text-center">
            <p class="text-sm font-medium text-ink-700">Consultez également notre politique de confidentialité.</p>
            <a href="{{ route('company.confidentialite') }}"
                class="btn btn-primary mt-4">
                Politique de confidentialité
            </a>
        </div>

    </div>
    </div>
@endsection