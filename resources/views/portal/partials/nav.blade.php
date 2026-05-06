@php
    $companyDisplayName = $company?->name ?: $company?->company_name ?: config('app.name');
    $currentRoute = request()->route()->getName();
    $navItems = [
        ['route' => 'portal.index',         'label' => __('erp.portal.nav.invoices'),      'icon' => '🧾'],
        ['route' => 'portal.quotes',         'label' => __('erp.portal.nav.quotes'),        'icon' => '📋'],
        ['route' => 'portal.documents',      'label' => __('erp.portal.nav.documents'),     'icon' => '📁'],
        ['route' => 'portal.projects',       'label' => __('erp.portal.nav.projects'),      'icon' => '🚀'],
        ['route' => 'portal.tickets',        'label' => __('erp.portal.nav.tickets'),       'icon' => '🎫'],
        ['route' => 'portal.activity',       'label' => __('erp.portal.nav.activity'),      'icon' => '📜'],
        ['route' => 'portal.conversations',  'label' => __('erp.portal.nav.conversations'), 'icon' => '💬'],
    ];
@endphp

<div class="topbar">
    <div style="display:flex;align-items:center;gap:16px;flex:1;min-width:0;">
        @if(!empty($logoDataUri ?? null))
            <img src="{{ $logoDataUri }}" alt="{{ $companyDisplayName }}" style="max-height:36px;max-width:140px;flex-shrink:0;">
        @endif
        <div style="min-width:0;">
            <div class="topbar-brand">{{ $companyDisplayName }}</div>
            <div class="topbar-sub">{{ __('erp.portal.secure_portal') }}</div>
        </div>
    </div>
    <div class="topbar-lang" style="flex-shrink:0;">
        @php $locale = session('portal_locale', 'fr'); @endphp
        <form method="POST" action="{{ route('portal.language', ['token' => $token]) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="locale" value="{{ $locale === 'fr' ? 'en' : 'fr' }}">
            <button type="submit" class="lang-btn">{{ $locale === 'fr' ? '🇬🇧 EN' : '🇫🇷 FR' }}</button>
        </form>
    </div>
</div>

<nav class="portal-nav">
    <div class="portal-nav-inner">
        <div class="nav-client">{{ $client->company_name ?: $client->contact_name }}</div>
        @foreach($navItems as $item)
            @php
                $isActive = $currentRoute === $item['route'];
                $href = route($item['route'], ['token' => $token]);
            @endphp
            <a href="{{ $href }}" class="nav-item {{ $isActive ? 'nav-item--active' : '' }}">
                <span class="nav-icon">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
