@extends('layouts.public')

@section('title', 'Politique de confidentialité — ' . $companyName)
@section('meta_description', 'Politique de confidentialité de ' . $companyName . '. Comment nous collectons, utilisons et protégeons vos données personnelles.')

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
            <h1 class="mt-4 text-4xl font-black tracking-tight text-[#002045]">Politique de confidentialité</h1>
            <p class="mt-3 text-sm text-[#43474e]">Dernière mise à jour : {{ now()->format('d F Y') }}</p>
        </div>

        <div class="prose prose-slate max-w-none space-y-10 text-[#0b1c30]">

            <section>
                <h2 class="text-xl font-bold text-[#002045]">1. Responsable du traitement</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Le responsable du traitement de vos données personnelles est <strong>{{ $companyName }}</strong>,
                    @if($companyAddress) dont le siège social est situé au {{ $companyAddress }}.@endif
                    @if($companyEmail) Pour toute question relative à la protection de vos données, vous pouvez nous
                        contacter à : <a href="mailto:{{ $companyEmail }}"
                    class="text-[#005048] underline">{{ $companyEmail }}</a>.@endif
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">2. Données collectées</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">Nous collectons les données suivantes lorsque vous utilisez
                    nos services ou formulaires de contact :</p>
                <ul class="mt-3 space-y-1 list-disc pl-6 text-[#43474e]">
                    <li>Nom complet et nom d'entreprise</li>
                    <li>Adresse e-mail professionnelle</li>
                    <li>Numéro de téléphone (si communiqué)</li>
                    <li>Message et intention de la prise de contact</li>
                    <li>Données de navigation (adresse IP, navigateur, pages visitées)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">3. Finalités du traitement</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">Vos données sont utilisées pour :</p>
                <ul class="mt-3 space-y-1 list-disc pl-6 text-[#43474e]">
                    <li>Répondre à vos demandes de contact et de démonstration</li>
                    <li>Vous envoyer des informations sur nos produits et services (avec votre consentement)</li>
                    <li>Améliorer nos services et l'expérience utilisateur</li>
                    <li>Respecter nos obligations légales et contractuelles</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">4. Base légale</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Le traitement de vos données repose sur votre consentement explicite (formulaire de contact),
                    l'exécution d'un contrat ou de mesures précontractuelles, ainsi que nos intérêts légitimes à améliorer
                    nos services.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">5. Conservation des données</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Vos données sont conservées pendant une durée maximale de <strong>3 ans</strong> à compter de votre
                    dernière interaction avec nous. Les données de facturation sont conservées conformément aux obligations
                    légales applicables (10 ans).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">6. Partage des données</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Nous ne vendons ni ne louons vos données personnelles à des tiers. Elles peuvent être partagées
                    uniquement avec nos sous-traitants techniques (hébergement, messagerie) dans le strict cadre de la
                    fourniture de nos services, et soumis à des garanties contractuelles adéquates.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">7. Vos droits</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">Conformément à la réglementation applicable, vous disposez
                    des droits suivants :</p>
                <ul class="mt-3 space-y-1 list-disc pl-6 text-[#43474e]">
                    <li><strong>Accès</strong> : consulter les données que nous détenons sur vous</li>
                    <li><strong>Rectification</strong> : corriger des données inexactes</li>
                    <li><strong>Effacement</strong> : demander la suppression de vos données</li>
                    <li><strong>Opposition</strong> : vous opposer au traitement à des fins de prospection</li>
                    <li><strong>Portabilité</strong> : recevoir vos données dans un format structuré</li>
                </ul>
                @if($companyEmail)
                    <p class="mt-3 leading-relaxed text-[#43474e]">
                        Pour exercer ces droits, contactez-nous à : <a href="mailto:{{ $companyEmail }}"
                            class="text-[#005048] underline">{{ $companyEmail }}</a>
                    </p>
                @endif
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">8. Sécurité</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données
                    contre tout accès non autorisé, altération, divulgation ou destruction, notamment le chiffrement des
                    données en transit (HTTPS) et au repos.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">9. Cookies</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Pour plus d'informations sur notre utilisation des cookies, consultez notre <a
                        href="{{ route('company.cookies') }}" class="text-[#005048] underline">Politique en matière de
                        cookies</a>.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#002045]">10. Modification de la politique</h2>
                <p class="mt-3 leading-relaxed text-[#43474e]">
                    Nous nous réservons le droit de modifier cette politique à tout moment. Toute modification substantielle
                    sera communiquée par e-mail ou par affichage sur notre site. La date de dernière mise à jour est
                    indiquée en haut de cette page.
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-2xl bg-[#eff4ff] p-8 text-center">
            <p class="text-sm font-medium text-[#43474e]">Des questions sur vos données ?</p>
            <a href="{{ route('company.presentation') }}#contact"
                class="mt-4 inline-block rounded-xl bg-[#002045] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                Nous contacter
            </a>
        </div>

    </div>
@endsection