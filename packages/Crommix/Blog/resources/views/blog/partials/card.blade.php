{{-- Post card used on listing pages. Expects $post. --}}
<article class="group flex flex-col overflow-hidden rounded-[1.25rem] border border-sand-200 bg-white transition hover:-translate-y-0.5 hover:border-sand-300 hover:shadow-[0_18px_40px_-24px_rgb(46_38_32/0.35)]">
    <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/9] overflow-hidden bg-bogolan" tabindex="-1" aria-hidden="true">
        @if($post->coverImageUrl())
            <img src="{{ $post->coverImageUrl() }}" alt="" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="flex h-full w-full items-center justify-center">
                <span class="display flex h-20 w-20 items-center justify-center rounded-full bg-sand-50 text-4xl text-ink-700 shadow-sm">{{ mb_strtoupper(mb_substr($post->title, 0, 1)) }}</span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-6">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-ink-500">
            @if($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" class="badge badge-muted transition hover:bg-sand-200">
                    {{ $post->category->name }}
                </a>
            @endif
            @include('crommix-blog::blog.partials.stage', ['post' => $post])
            <span>{{ $post->publicDate()?->translatedFormat('d M Y') }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->readingMinutes() }} min</span>
        </div>

        <h2 class="display text-xl leading-snug">
            <a href="{{ route('blog.show', $post->slug) }}" class="transition hover:text-terra-700">{{ $post->title }}</a>
        </h2>

        <p class="mt-3 flex-1 text-sm leading-relaxed text-ink-600">{{ $post->summary(160) }}</p>

        <a href="{{ route('blog.show', $post->slug) }}" class="link-arrow mt-5 text-sm">
            Lire l’article <x-site.icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
</article>
