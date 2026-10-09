{{--
    Contact form posting to company.presentation.contact.
    Props: intents (value => label), default (selected intent), source, companyField.
--}}
@props([
    'intents' => [
        'Consultation Digitale' => 'Consultation digitale',
        'Demande démo DMS' => 'Demande de démo DMS',
        'Implémentation ERP' => 'Implémentation ERP',
        'Gestion de Flotte' => 'Gestion de flotte',
        'Autre Enquête' => 'Autre demande',
    ],
    'default' => 'Consultation Digitale',
    'source' => null,
    'companyField' => true,
    'companyLabel' => 'Entreprise',
    'companyPlaceholder' => 'Votre organisation',
    'intentLabel' => 'Sujet',
    'submitLabel' => 'Envoyer la demande',
])
@php $selected = old('intent', request('intent', $default)); @endphp

@if (session('status'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-kola-100 bg-kola-50 px-5 py-4 text-sm font-medium text-kola-700" role="status">
        <x-site.icon name="check" class="mt-0.5 h-5 w-5" /> {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('company.presentation.contact') }}" {{ $attributes->merge(['class' => 'grid gap-5 sm:grid-cols-2']) }}>
    @csrf
    @if ($source)
        <input type="hidden" name="source" value="{{ $source }}">
    @endif

    <div class="{{ $companyField ? '' : 'sm:col-span-2' }}">
        <label for="name" class="field-label">Nom complet</label>
        <input id="name" name="name" value="{{ old('name') }}" type="text" autocomplete="name" placeholder="Aminata Traoré" class="field" required>
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    @if ($companyField)
        <div>
            <label for="company_name" class="field-label">{{ $companyLabel }} <span class="font-normal text-ink-500">(optionnel)</span></label>
            <input id="company_name" name="company_name" value="{{ old('company_name') }}" type="text" autocomplete="organization" placeholder="{{ $companyPlaceholder }}" class="field">
            @error('company_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    @endif

    <div>
        <label for="email" class="field-label">E-mail professionnel</label>
        <input id="email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" placeholder="vous@entreprise.ml" class="field" required>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="intent" class="field-label">{{ $intentLabel }}</label>
        <select id="intent" name="intent" class="field">
            @foreach ($intents as $value => $label)
                <option value="{{ $value }}" @selected($selected === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('intent') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="message" class="field-label">Message</label>
        <textarea id="message" name="message" rows="5" placeholder="Décrivez brièvement votre besoin…" class="field">{{ old('message') }}</textarea>
        @error('message') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col gap-3 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-ink-500">Vos coordonnées servent uniquement à répondre à votre demande.</p>
        <button type="submit" class="btn btn-accent">
            {{ $submitLabel }} <x-site.icon name="arrow-right" class="h-4 w-4" />
        </button>
    </div>
</form>
