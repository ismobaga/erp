{{-- Dark call-to-action band. Props: title, text, href, label. --}}
@props(['title', 'text' => null, 'href' => null, 'label' => 'Nous contacter'])
<section class="site-container py-20">
    <div class="relative overflow-hidden rounded-[2rem] bg-bogolan-dark px-8 py-12 text-sand-50 sm:px-12 lg:px-16 lg:py-16">
        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-terra-600/25 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 class="display text-3xl text-sand-50 sm:text-4xl">{{ $title }}</h2>
                @if ($text)
                    <p class="mt-4 text-base leading-relaxed text-sand-200/85">{{ $text }}</p>
                @endif
            </div>
            <a href="{{ $href ?? route('company.contact') }}" class="btn btn-on-dark shrink-0">
                {{ $label }} <x-site.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
</section>
