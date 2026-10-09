@extends('crommix-blog::layouts.public')

@section('title', ($heading === 'Blog' ? '' : $heading . ' — ') . ($blogCompany->name ?? 'CROMMIX') . ' — Blog')
@section('meta_description', $intro ?: 'Articles opérationnels, retours terrain et stratégies de croissance.')
@if($activeCategory)
    @section('canonical', route('blog.category', $activeCategory->slug))
@elseif($activeTag)
    @section('canonical', route('blog.tag', $activeTag->slug))
@endif

@if($search !== '')
    @push('meta')
        <meta name="robots" content="noindex, follow">
    @endpush
@endif

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden border-b border-sand-200">
        <div class="absolute inset-y-0 right-0 hidden w-[28%] bg-bogolan lg:block" aria-hidden="true">
            <div class="absolute inset-0 bg-gradient-to-r from-sand-50 to-transparent"></div>
        </div>
        <div class="site-container relative py-16 lg:py-24">
            @if($activeCategory || $activeTag)
                <a href="{{ route('blog.index') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-ink-600 transition hover:text-ink-900">
                    <x-site.icon name="arrow-right" class="h-4 w-4 rotate-180" /> Tous les articles
                </a>
            @endif
            <p class="eyebrow">{{ $activeCategory ? 'Catégorie' : ($activeTag ? 'Mot-clé' : 'Journal') }}</p>
            <h1 class="display mt-5 text-5xl sm:text-6xl">{{ $heading }}</h1>
            @if(filled($intro))
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">{{ $intro }}</p>
            @endif

            @if(! $activeCategory && ! $activeTag)
                <form method="GET" action="{{ route('blog.index') }}" role="search" class="mt-9 flex max-w-xl gap-2">
                    <label for="blog-search" class="sr-only">Rechercher un article</label>
                    <input id="blog-search" type="search" name="q" value="{{ $search }}" maxlength="100"
                           placeholder="Rechercher un article…" class="field min-w-0 flex-1 rounded-full px-5">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </form>
            @endif

            @if($categories->isNotEmpty())
                <nav aria-label="Catégories" class="mt-8 flex flex-wrap gap-2">
                    <a href="{{ route('blog.index') }}" @if(! $activeCategory) aria-current="page" @endif
                       class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ ! $activeCategory ? 'bg-ink-900 text-sand-50' : 'border border-sand-300 bg-white text-ink-700 hover:border-ink-900' }}">
                        Tout
                    </a>
                    @foreach($categories as $category)
                        @php $isActive = $activeCategory?->is($category); @endphp
                        <a href="{{ route('blog.category', $category->slug) }}" @if($isActive) aria-current="page" @endif
                           class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $isActive ? 'bg-ink-900 text-sand-50' : 'border border-sand-300 bg-white text-ink-700 hover:border-ink-900' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>

    <section class="site-container py-16 lg:py-20">
        {{-- Featured post --}}
        @if($featured)
            <article class="group mb-12 grid overflow-hidden rounded-[1.75rem] border border-sand-200 bg-white {{ $featured->coverImageUrl() ? 'md:grid-cols-2' : '' }}">
                @if($featured->coverImageUrl())
                    <a href="{{ route('blog.show', $featured->slug) }}" class="block min-h-64 overflow-hidden bg-sand-100" tabindex="-1" aria-hidden="true">
                        <img src="{{ $featured->coverImageUrl() }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                    </a>
                @endif
                <div class="flex flex-col justify-center p-8 lg:p-12">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-soon">À la une</span>
                        @include('crommix-blog::blog.partials.stage', ['post' => $featured])
                    </div>
                    <h2 class="display mt-5 text-3xl lg:text-4xl">
                        <a href="{{ route('blog.show', $featured->slug) }}" class="transition hover:text-terra-700">{{ $featured->title }}</a>
                    </h2>
                    <p class="mt-4 leading-relaxed text-ink-600">{{ $featured->summary(220) }}</p>
                    <p class="mt-6 text-sm text-ink-500">
                        {{ $featured->publicDate()?->translatedFormat('d M Y') }} · {{ $featured->readingMinutes() }} min de lecture
                    </p>
                </div>
            </article>
        @endif

        @if($search !== '')
            <p class="mb-8 text-sm text-ink-600">
                {{ $posts->total() }} résultat{{ $posts->total() > 1 ? 's' : '' }} pour « {{ $search }} »
                — <a href="{{ route('blog.index') }}" class="font-semibold text-terra-700 underline underline-offset-4">effacer</a>
            </p>
        @endif

        @if($posts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                    @include('crommix-blog::blog.partials.card', ['post' => $post])
                @endforeach
            </div>
        @elseif(! $featured)
            <div class="rounded-[1.5rem] border border-dashed border-sand-300 bg-sand-100/60 px-8 py-16 text-center">
                <span class="icon-tile mx-auto"><x-site.icon name="document" class="h-5 w-5" /></span>
                <p class="mt-5 font-semibold text-ink-800">
                    {{ $search !== '' ? 'Aucun article ne correspond à votre recherche.' : 'Aucun article publié pour le moment.' }}
                </p>
                <p class="mt-1 text-sm text-ink-500">Revenez bientôt.</p>
            </div>
        @endif

        @if($posts->hasPages())
            <nav aria-label="Pagination" class="mt-12 flex items-center justify-between gap-4">
                @if($posts->previousPageUrl())
                    <a href="{{ $posts->previousPageUrl() }}" rel="prev" class="btn btn-outline btn-sm">
                        <x-site.icon name="arrow-right" class="h-4 w-4 rotate-180" /> Précédent
                    </a>
                @else
                    <span></span>
                @endif
                <span class="text-sm text-ink-500">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
                @if($posts->nextPageUrl())
                    <a href="{{ $posts->nextPageUrl() }}" rel="next" class="btn btn-outline btn-sm">
                        Suivant <x-site.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    </section>
@endsection
