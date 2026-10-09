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
        <header class="border-b border-sand-200">
            <div class="site-container py-14 lg:py-20">
                <div class="mx-auto max-w-3xl">
                    <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-ink-600 transition hover:text-ink-900">
                        <x-site.icon name="arrow-right" class="h-4 w-4 rotate-180" /> Retour au blog
                    </a>

                    <div class="mt-8 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-ink-500">
                        @if($post->category)
                            <a href="{{ route('blog.category', $post->category->slug) }}" class="badge badge-muted transition hover:bg-sand-200">
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

                    <h1 class="display mt-6 text-4xl sm:text-5xl">{{ $post->title }}</h1>

                    @if(filled($post->excerpt))
                        <p class="mt-6 text-xl leading-relaxed text-ink-600">{{ $post->excerpt }}</p>
                    @endif
                </div>
            </div>
        </header>

        <div class="site-container py-12 lg:py-16">
            @if($post->coverImageUrl())
                <figure class="mx-auto mb-12 max-w-5xl overflow-hidden rounded-[1.75rem] border border-sand-200 bg-sand-100">
                    <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->cover_image_alt ?: $post->title }}" class="aspect-[16/9] w-full object-cover">
                </figure>
            @endif

            <div class="mx-auto max-w-3xl">
                <div class="blog-content">
                    {{ $post->renderedContent() }}
                </div>

                @if($post->tags->isNotEmpty())
                    <div class="mt-12 flex flex-wrap gap-2 border-t border-sand-200 pt-8">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.tag', $tag->slug) }}" rel="tag"
                               class="rounded-full border border-sand-300 px-3 py-1 text-sm text-ink-700 transition hover:border-ink-900 hover:text-ink-900">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-12 flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-sand-200 bg-sand-100 p-6">
                    <p class="font-medium text-ink-800">Un projet en lien avec cet article ?</p>
                    <a href="{{ route('company.contact') }}" class="btn btn-primary btn-sm">Nous contacter</a>
                </div>
            </div>
        </div>
    </article>

    @if($related->isNotEmpty())
        <section class="border-t border-sand-200 bg-white py-16 lg:py-20" aria-labelledby="related-heading">
            <div class="site-container">
                <h2 id="related-heading" class="display text-3xl">À lire aussi</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('crommix-blog::blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
