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
    <section class="bg-[#f8f9ff] py-16 lg:py-20">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            @if($activeCategory || $activeTag)
                <a href="{{ route('blog.index') }}"
                   class="mb-6 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-[#43474e] transition hover:text-[#002045]">
                    ← Tous les articles
                </a>
            @endif
            <span class="block w-fit rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">
                {{ $activeCategory ? 'Catégorie' : ($activeTag ? 'Mot-clé' : 'Journal') }}
            </span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">{{ $heading }}</h1>
            @if(filled($intro))
                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-[#43474e]">{{ $intro }}</p>
            @endif

            @if(! $activeCategory && ! $activeTag)
                <form method="GET" action="{{ route('blog.index') }}" role="search" class="mt-8 flex max-w-xl gap-2">
                    <label for="blog-search" class="sr-only">Rechercher un article</label>
                    <input id="blog-search" type="search" name="q" value="{{ $search }}" maxlength="100"
                           placeholder="Rechercher un article…"
                           class="min-w-0 flex-1 rounded-xl border border-[#c4c6cf]/60 bg-white px-4 py-2.5 text-sm text-[#0b1c30] focus:border-[#002045] focus:outline-none focus:ring-2 focus:ring-[#002045]/20">
                    <button type="submit"
                            class="rounded-xl bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        Rechercher
                    </button>
                </form>
            @endif

            @if($categories->isNotEmpty())
                <nav aria-label="Catégories" class="mt-8 flex flex-wrap gap-2">
                    <a href="{{ route('blog.index') }}"
                       class="rounded-full px-4 py-1.5 text-xs font-semibold transition {{ ! $activeCategory ? 'bg-[#002045] text-white' : 'bg-white text-[#2d476f] ring-1 ring-[#dce9ff] hover:bg-[#eff4ff]' }}">
                        Tout
                    </a>
                    @foreach($categories as $category)
                        @php $isActive = $activeCategory?->is($category); @endphp
                        <a href="{{ route('blog.category', $category->slug) }}" @if($isActive) aria-current="page" @endif
                           class="rounded-full px-4 py-1.5 text-xs font-semibold transition {{ $isActive ? 'bg-[#002045] text-white' : 'bg-white text-[#2d476f] ring-1 ring-[#dce9ff] hover:bg-[#eff4ff]' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>

    <section class="bg-[#eff4ff] py-16">
        <div class="mx-auto max-w-6xl px-6 lg:px-8">
            {{-- Featured post --}}
            @if($featured)
                <article class="group mb-10 grid overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-[#dce9ff] {{ $featured->coverImageUrl() ? 'md:grid-cols-2' : '' }}">
                    @if($featured->coverImageUrl())
                        <a href="{{ route('blog.show', $featured->slug) }}" class="block min-h-56 overflow-hidden bg-[#dce9ff]" tabindex="-1" aria-hidden="true">
                            <img src="{{ $featured->coverImageUrl() }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                        </a>
                    @endif
                    <div class="flex flex-col justify-center p-8 lg:p-10">
                        <span class="w-fit rounded-full bg-[#d4a574]/20 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#7a5020]">À la une</span>
                        <h2 class="mt-4 text-2xl font-black leading-tight text-[#002045] lg:text-3xl">
                            <a href="{{ route('blog.show', $featured->slug) }}" class="transition hover:text-[#1a365d]">{{ $featured->title }}</a>
                        </h2>
                        <p class="mt-4 leading-relaxed text-[#43474e]">{{ $featured->summary(220) }}</p>
                        <p class="mt-4 text-xs text-[#57657a]">
                            {{ $featured->publicDate()?->translatedFormat('d M Y') }} · {{ $featured->readingMinutes() }} min de lecture
                        </p>
                    </div>
                </article>
            @endif

            @if($search !== '')
                <p class="mb-6 text-sm text-[#43474e]">
                    {{ $posts->total() }} résultat{{ $posts->total() > 1 ? 's' : '' }} pour « {{ $search }} »
                    — <a href="{{ route('blog.index') }}" class="font-semibold text-[#002045] underline">effacer</a>
                </p>
            @endif

            @if($posts->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        @include('crommix-blog::blog.partials.card', ['post' => $post])
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border-2 border-dashed border-[#c4c6cf]/40 bg-white p-12 text-center">
                    <div class="mb-3 text-4xl opacity-30">📝</div>
                    <p class="text-sm font-semibold text-[#57657a]">
                        {{ $search !== '' ? 'Aucun article ne correspond à votre recherche.' : 'Aucun article publié pour le moment.' }}
                    </p>
                    <p class="mt-1 text-xs text-[#57657a]/70">Revenez bientôt.</p>
                </div>
            @endif

            @if($posts->hasPages())
                <nav aria-label="Pagination" class="mt-10 flex items-center justify-between">
                    @if($posts->previousPageUrl())
                        <a href="{{ $posts->previousPageUrl() }}" rel="prev"
                           class="inline-flex items-center gap-2 rounded-xl border border-[#c4c6cf]/40 bg-white px-5 py-2.5 text-sm font-semibold text-[#002045] transition hover:bg-[#eff4ff]">
                            ← Précédent
                        </a>
                    @else
                        <span></span>
                    @endif
                    <span class="text-xs text-[#57657a]">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
                    @if($posts->nextPageUrl())
                        <a href="{{ $posts->nextPageUrl() }}" rel="next"
                           class="inline-flex items-center gap-2 rounded-xl border border-[#c4c6cf]/40 bg-white px-5 py-2.5 text-sm font-semibold text-[#002045] transition hover:bg-[#eff4ff]">
                            Suivant →
                        </a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
