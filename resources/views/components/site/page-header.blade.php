{{-- Inner-page hero. Props: eyebrow, title, lead. Slot: optional actions. --}}
@props(['eyebrow' => null, 'title', 'lead' => null])
<section class="relative overflow-hidden border-b border-sand-200">
    <div class="absolute inset-y-0 right-0 hidden w-[28%] bg-bogolan lg:block" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-r from-sand-50 to-transparent"></div>
    </div>
    <div class="site-container relative py-20 lg:py-28">
        <div class="max-w-3xl">
            @if ($eyebrow)
                <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 class="display mt-5 text-4xl sm:text-5xl lg:text-6xl">{{ $title }}</h1>
            @if ($lead)
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">{{ $lead }}</p>
            @endif
            @if (trim($slot) !== '')
                <div class="mt-9 flex flex-wrap gap-3">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
