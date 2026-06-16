@extends('crommix-blog::layouts.public')

@section('title', ($companyName ?? 'CROMMIX') . ' — Blog')
@section('meta_description', 'Articles opérationnels, retours terrain et stratégies de croissance pour structurer vos opérations.')

@section('content')
    {{-- Hero --}}
    <section class="bg-[#f8f9ff] py-20">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            <span class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Journal</span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">Blog</h1>
            <p class="mt-4 max-w-xl text-lg leading-relaxed text-[#43474e]">
                Articles opérationnels, retours terrain et stratégies de croissance pour structurer vos opérations.
            </p>
        </div>
    </section>

    {{-- Posts --}}
    <section class="bg-[#eff4ff] py-16">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            @forelse($posts as $post)
                <article class="mb-5 rounded-2xl bg-white p-7 shadow-sm ring-1 ring-[#dce9ff] transition hover:shadow-md">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="rounded-full bg-[#dce9ff] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#2d476f]">Article</span>
                        <span class="text-xs text-[#57657a]">
                            {{ optional($post->published_at)->format('d/m/Y') ?? 'Non planifié' }}
                            @if($post->author)
                                · {{ $post->author->name }}
                            @endif
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-[#002045]">
                        <a href="{{ route('blog.show', $post->slug) }}" class="transition hover:text-[#1a365d]">
                            {{ $post->title }}
                        </a>
                    </h2>

                    @if(filled($post->excerpt))
                        <p class="mt-3 text-sm leading-relaxed text-[#43474e]">{{ $post->excerpt }}</p>
                    @endif

                    <div class="mt-5">
                        <a href="{{ route('blog.show', $post->slug) }}"
                           class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest text-[#002045] transition hover:opacity-70">
                            Lire l'article →
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border-2 border-dashed border-[#c4c6cf]/40 bg-white p-12 text-center">
                    <div class="mb-3 text-4xl opacity-30">📝</div>
                    <p class="text-sm font-semibold text-[#57657a]">Aucun article publié pour le moment.</p>
                    <p class="mt-1 text-xs text-[#57657a]/70">Revenez bientôt.</p>
                </div>
            @endforelse

            @if($posts->hasPages())
                <div class="mt-8 flex items-center justify-between">
                    @if($posts->previousPageUrl())
                        <a href="{{ $posts->previousPageUrl() }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-[#c4c6cf]/40 bg-white px-5 py-2.5 text-sm font-semibold text-[#002045] transition hover:bg-[#eff4ff]">
                            ← Précédent
                        </a>
                    @else
                        <span></span>
                    @endif
                    @if($posts->nextPageUrl())
                        <a href="{{ $posts->nextPageUrl() }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-[#c4c6cf]/40 bg-white px-5 py-2.5 text-sm font-semibold text-[#002045] transition hover:bg-[#eff4ff]">
                            Suivant →
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
