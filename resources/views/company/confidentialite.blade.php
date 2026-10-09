@extends('layouts.public')

@section('title', 'Politique de confidentialité — ' . $companyName)
@section('meta_description', 'Politique de confidentialité de ' . $companyName . '. Comment nous collectons, utilisons et protégeons vos données personnelles.')

@section('content')
    <x-site.page-header eyebrow="Informations légales" title="Politique de confidentialité" />

    <div class="site-container py-16 lg:py-20">
        <div class="mx-auto max-w-3xl">
        <p class="mb-10 text-sm text-ink-500">Dernière mise à jour : {{ now()->translatedFormat('d F Y') }}</p>

        <div class="legal-content">

            <section>
                <h2>1. Responsable du traitement</h2>
                <p>
                    Le responsable du traitement de vos données personnelles est <strong>{{ $companyName }}</strong>,
                    @if($companyAddress) dont le siège social est situé au {{ $companyAddress }}.@endif
                    @if($companyEmail) Pour toute question relative à la protection de vos données, vous pouvez nous
                        contacter à : <a href="mailto:{{ $companyEmail }}"
                   >{{ $companyEmail }}</a>.@endif
                </p>
            </section>

            <section>
                <h2>2. Données collectées</h2>
                <p>Nous collectons les données suivantes lorsque vous utilisez
                    nos services ou formulaires de contact :</p>
                <ul>
                    <li>Nom complet et nom d'entreprise</li>
                    <li>Adresse e-mail professionnelle</li>
                    <li>Numéro de téléphone (si communiqué)</li>
                    <li>Message et intention de la prise de contact</li>
                    <li>Données de navigation (adresse IP, navigateur, pages visitées)</li>
                </ul>
            </section>

            <section>
                <h2>3. Finalités du traitement</h2>
                <p>Vos données sont utilisées pour :</p>
                <ul>
                    <li>Répondre à vos demandes de contact et de démonstration</li>
                    <li>Vous envoyer des informations sur nos produits et services (avec votre consentement)</li>
                    <li>Améliorer nos services et l'expérience utilisateur</li>
                    <li>Respecter nos obligations légales et contractuelles</li>
                </ul>
            </section>

            <section>
                <h2>4. Base légale</h2>
                <p>
                    Le traitement de vos données repose sur votre consentement explicite (formulaire de contact),
                    l'exécution d'un contrat ou de mesures précontractuelles, ainsi que nos intérêts légitimes à améliorer
                    nos services.
                </p>
            </section>

            <section>
                <h2>5. Conservation des données</h2>
                <p>
                    Vos données sont conservées pendant une durée maximale de <strong>3 ans</strong> à compter de votre
                    dernière interaction avec nous. Les données de facturation sont conservées conformément aux obligations
                    légales applicables (10 ans).
                </p>
            </section>

            <section>
                <h2>6. Partage des données</h2>
                <p>
                    Nous ne vendons ni ne louons vos données personnelles à des tiers. Elles peuvent être partagées
                    uniquement avec nos sous-traitants techniques (hébergement, messagerie) dans le strict cadre de la
                    fourniture de nos services, et soumis à des garanties contractuelles adéquates.
                </p>
            </section>

            <section>
                <h2>7. Vos droits</h2>
                <p>Conformément à la réglementation applicable, vous disposez
                    des droits suivants :</p>
                <ul>
                    <li><strong>Accès</strong> : consulter les données que nous détenons sur vous</li>
                    <li><strong>Rectification</strong> : corriger des données inexactes</li>
                    <li><strong>Effacement</strong> : demander la suppression de vos données</li>
                    <li><strong>Opposition</strong> : vous opposer au traitement à des fins de prospection</li>
                    <li><strong>Portabilité</strong> : recevoir vos données dans un format structuré</li>
                </ul>
                @if($companyEmail)
                    <p>
                        Pour exercer ces droits, contactez-nous à : <a href="mailto:{{ $companyEmail }}"
                           >{{ $companyEmail }}</a>
                    </p>
                @endif
            </section>

            <section>
                <h2>8. Sécurité</h2>
                <p>
                    Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données
                    contre tout accès non autorisé, altération, divulgation ou destruction, notamment le chiffrement des
                    données en transit (HTTPS) et au repos.
                </p>
            </section>

            <section>
                <h2>9. Cookies</h2>
                <p>
                    Pour plus d'informations sur notre utilisation des cookies, consultez notre <a
                        href="{{ route('company.cookies') }}">Politique en matière de
                        cookies</a>.
                </p>
            </section>

            <section>
                <h2>10. Modification de la politique</h2>
                <p>
                    Nous nous réservons le droit de modifier cette politique à tout moment. Toute modification substantielle
                    sera communiquée par e-mail ou par affichage sur notre site. La date de dernière mise à jour est
                    indiquée en haut de cette page.
                </p>
            </section>

        </div>

        <div class="mt-16 rounded-[1.5rem] border border-sand-200 bg-sand-100 p-8 text-center">
            <p class="text-sm font-medium text-ink-700">Des questions sur vos données ?</p>
            <a href="{{ route('company.presentation') }}#contact"
                class="btn btn-primary mt-4">
                Nous contacter
            </a>
        </div>

    </div>
    </div>
@endsection