@extends('layouts.public')

@section('title', 'Politique en matière de cookies — ' . $companyName)
@section('meta_description', 'Comment ' . $companyName . ' utilise les cookies et technologies similaires sur son site.')

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
            <h1 class="mt-4 text-4xl font-black tracking-tight text-[#002045]">Politique en matière de cookies</h1>
            <p class="mt-3 text-sm text-[#43474e]">Dernière mise à jour : {{ now()->format('d F Y') }}</p>
        </div>

        <div class="prose prose-slate max-w-none space-y-10 text-[#0b1c30]">

            <section>
                <h2 class="text-xl font-bold text-[#002045]">1. Qu'est-ce qu'un cookie ?</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur, tablette, mobile) lors de la
                    visite d'un site web. Il permet au site de mémoriser vos préférences et de vous offrir une expérience
                    personnalisée lors de vos visites ultérieures.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">2. Cookies utilisés sur ce site</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">Le site de {{ $companyName }} utilise les catégories de
                    cookies suivantes :</p>

                <div class="mt-6 overflow-hidden rounded-xl border border-[#dce9ff]">
                    <table class="w-full text-sm">
                        <thead class="bg-[#eff4ff]">
                            <tr>
                                <th class="px-5 py-3 text-left font-bold text-[#002045]">Type</th>
                                <th class="px-5 py-3 text-left font-bold text-[#002045]">Finalité</th>
                                <th class="px-5 py-3 text-left font-bold text-[#002045]">Durée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#dce9ff]">
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-[#002045]">Essentiels</td>
                                <td class="px-5 py-4 text-[#43474e]">Session, sécurité CSRF, préférences de langue</td>
                                <td class="px-5 py-4 text-[#43474e]">Session / 1 an</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-[#002045]">Fonctionnels</td>
                                <td class="px-5 py-4 text-[#43474e]">Mémorisation de vos préférences d'affichage</td>
                                <td class="px-5 py-4 text-[#43474e]">6 mois</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-[#002045]">Analytiques</td>
                                <td class="px-5 py-4 text-[#43474e]">Mesure d'audience anonymisée (pages vues, durée)</td>
                                <td class="px-5 py-4 text-[#43474e]">13 mois</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">3. Cookies strictement nécessaires</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Ces cookies sont indispensables au fonctionnement du site. Ils permettent notamment d'assurer la
                    sécurité des formulaires (protection CSRF) et de maintenir votre session lors de votre navigation. Ils
                    ne peuvent pas être désactivés.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">4. Cookies analytiques</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Ces cookies nous aident à comprendre comment les visiteurs utilisent notre site (pages les plus
                    consultées, parcours de navigation, taux de rebond). Ces données sont collectées de manière anonyme et
                    ne permettent pas de vous identifier personnellement.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">5. Gestion de vos préférences</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Vous pouvez à tout moment configurer votre navigateur pour refuser ou supprimer les cookies. Voici
                    comment procéder selon votre navigateur :
                </p>
                <ul class="mt-3 space-y-1 list-disc pl-6 text-[#43474e]">
                    <li><strong>Chrome</strong> : Paramètres → Confidentialité et sécurité → Cookies</li>
                    <li><strong>Firefox</strong> : Paramètres → Vie privée et sécurité → Cookies</li>
                    <li><strong>Safari</strong> : Préférences → Confidentialité → Cookies</li>
                    <li><strong>Edge</strong> : Paramètres → Cookies et données de site</li>
                </ul>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Notez que la désactivation de certains cookies peut affecter le fonctionnement et la sécurité du site.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">6. Contact</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Pour toute question sur notre utilisation des cookies, contactez-nous à
                    @if($companyEmail)<a href="mailto:{{ $companyEmail }}"
                        class="text-[#005048] underline">{{ $companyEmail }}</a>
                    @else
                        notre équipe.
                    @endif
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-2xl bg-[#eff4ff] p-8 text-center">
            <p class="text-sm font-medium text-[#43474e]">Consultez également notre politique de confidentialité.</p>
            <a href="{{ route('company.confidentialite') }}"
                class="mt-4 inline-block rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                Politique de confidentialité
            </a>
        </div>

    </div>
@endsection