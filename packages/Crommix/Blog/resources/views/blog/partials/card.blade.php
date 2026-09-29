{{-- Post card used on listing pages. Expects $post. --}}
<article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-[#dce9ff] transition hover:shadow-md">
    <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/9] overflow-hidden bg-[#dce9ff]" tabindex="-1" aria-hidden="true">
        @if($post->coverImageUrl())
            <img src="{{ $post->coverImageUrl() }}" alt="" loading="lazy"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
        @else
            <div class="flex h-full w-full items-center justify-center text-4xl font-black text-[#2d476f]/30">
                {{ mb_strtoupper(mb_substr($post->title, 0, 1)) }}
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-6">
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-[#57657a]">
            @if($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}"
                   class="rounded-full bg-[#dce9ff] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#2d476f] transition hover:bg-[#c9dcff]">
                    {{ $post->category->name }}
                </a>
            @endif
            @include('crommix-blog::blog.partials.stage', ['post' => $post])
            <span>{{ $post->publicDate()?->translatedFormat('d M Y') }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->readingMinutes() }} min de lecture</span>
        </div>

        <h2 class="text-lg font-bold leading-snug text-[#002045]">
            <a href="{{ route('blog.show', $post->slug) }}" class="transition hover:text-[#1a365d]">
                {{ $post->title }}
            </a>
        </h2>

        <p class="mt-3 flex-1 text-sm leading-relaxed text-[#43474e]">{{ $post->summary(160) }}</p>

        <div class="mt-5">
            <a href="{{ route('blog.show', $post->slug) }}"
               class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest text-[#002045] transition hover:opacity-70">
                Lire l'article →
            </a>
        </div>
    </div>
</article>
