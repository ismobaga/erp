@extends('crommix-blog::layouts.public')

@section('title', $post->seo_title ?: $post->title . ' — ' . ($blogCompany->name ?? 'CROMMIX'))
@section('meta_description', $post->seo_description ?: $post->summary(160))
@section('canonical', route('blog.show', $post->slug))
@section('og_type', 'article')
@if($post->coverImageUrl())
    @section('og_image', $post->coverImageUrl())
@endif

@push('meta')
    @if($post->publicDate())
        <meta property="article:published_time" content="{{ $post->publicDate()->toAtomString() }}">
    @endif
    <meta property="article:modified_time" content="{{ $post->updated_at?->toAtomString() }}">
    @if($post->category)
        <meta property="article:section" content="{{ $post->category->name }}">
    @endif
    @foreach($post->tags as $tag)
        <meta property="article:tag" content="{{ $tag->name }}">
    @endforeach
    <script type="application/ld+json" nonce="{{ csp_nonce() }}">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->seo_description ?: $post->summary(160),
            'image' => $post->coverImageUrl(),
            'datePublished' => $post->publicDate()?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
            'publisher' => ['@type' => 'Organization', 'name' => $blogCompany->name ?? config('app.name')],
            'mainEntityOfPage' => route('blog.show', $post->slug),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
    </script>
@endpush

@section('content')
    <article>
        {{-- Article hero --}}
        <header class="bg-[#f8f9ff] py-16">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <a href="{{ route('blog.index') }}"
                   class="mb-6 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-[#43474e] transition hover:text-[#002045]">
                    ← Retour au blog
                </a>

                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-[#57657a]">
                    @if($post->category)
                        <a href="{{ route('blog.category', $post->category->slug) }}"
                           class="rounded-full bg-[#dce9ff] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#2d476f] transition hover:bg-[#c9dcff]">
                            {{ $post->category->name }}
                        </a>
                    @endif
                    @include('crommix-blog::blog.partials.stage', ['post' => $post])
                    @if($post->publicDate())
                        <time datetime="{{ $post->publicDate()->toDateString() }}">{{ $post->publicDate()->translatedFormat('d F Y') }}</time>
                    @endif
                    @if($post->author)
                        <span aria-hidden="true">·</span><span>{{ $post->author->name }}</span>
                    @endif
                    <span aria-hidden="true">·</span><span>{{ $post->readingMinutes() }} min de lecture</span>
                </div>

                <h1 class="mt-5 text-3xl font-black leading-tight tracking-tight text-[#002045] lg:text-4xl">
                    {{ $post->title }}
                </h1>

                @if(filled($post->excerpt))
                    <p class="mt-4 text-lg leading-relaxed text-[#43474e]">{{ $post->excerpt }}</p>
                @endif
            </div>
        </header>

        {{-- Article body --}}
        <section class="bg-[#eff4ff] py-12">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                @if($post->coverImageUrl())
                    <figure class="mb-8 overflow-hidden rounded-2xl shadow-sm ring-1 ring-[#dce9ff]">
                        <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->cover_image_alt ?: $post->title }}"
                             class="aspect-[16/9] w-full object-cover">
                    </figure>
                @endif

                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] lg:p-12">
                    <div class="blog-content">
                        {{ $post->renderedContent() }}
                    </div>

                    @if($post->tags->isNotEmpty())
                        <div class="mt-10 flex flex-wrap gap-2 border-t border-[#dce9ff] pt-6">
                            @foreach($post->tags as $tag)
                                <a href="{{ route('blog.tag', $tag->slug) }}" rel="tag"
                                   class="rounded-full bg-[#eff4ff] px-3 py-1 text-xs font-semibold text-[#2d476f] transition hover:bg-[#dce9ff]">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('blog.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-[#c4c6cf]/40 bg-white px-5 py-2.5 text-sm font-semibold text-[#002045] transition hover:bg-[#eff4ff]">
                        ← Tous les articles
                    </a>
                    <a href="{{ route('company.contact') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        Nous contacter
                    </a>
                </div>
            </div>
        </section>
    </article>

    @if($related->isNotEmpty())
        <section class="bg-[#f8f9ff] py-16" aria-labelledby="related-heading">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <h2 id="related-heading" class="mb-8 text-2xl font-black tracking-tight text-[#002045]">À lire aussi</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('crommix-blog::blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
