@extends('crommix-blog::layouts.public')

@section('title', $post->seo_title ?: $post->title)
@section('meta_description', $post->seo_description ?: ($post->excerpt ?: 'Article de blog ' . ($companyName ?? 'CROMMIX')))

@section('content')
    {{-- Article hero --}}
    <section class="bg-[#f8f9ff] py-16">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <a href="{{ route('blog.index') }}"
               class="mb-6 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-[#43474e] transition hover:text-[#002045]">
                ← Retour au blog
            </a>

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-[#dce9ff] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#2d476f]">Article</span>
                <span class="text-xs text-[#57657a]">
                    {{ optional($post->published_at)->format('d/m/Y') ?? '' }}
                    @if($post->author)
                        · {{ $post->author->name }}
                    @endif
                </span>
            </div>

            <h1 class="mt-5 text-3xl font-black leading-tight tracking-tight text-[#002045] lg:text-4xl">
                {{ $post->title }}
            </h1>

            @if(filled($post->excerpt))
                <p class="mt-4 text-lg leading-relaxed text-[#43474e]">{{ $post->excerpt }}</p>
            @endif
        </div>
    </section>

    {{-- Article body --}}
    <section class="bg-[#eff4ff] py-12">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] lg:p-12">
                <div class="prose prose-slate max-w-none
                            prose-headings:font-black prose-headings:text-[#002045] prose-headings:tracking-tight
                            prose-p:text-[#43474e] prose-p:leading-relaxed
                            prose-a:text-[#002045] prose-a:font-semibold hover:prose-a:opacity-70
                            prose-strong:text-[#002045]
                            prose-blockquote:border-l-[#002045] prose-blockquote:text-[#43474e]">
                    {!! nl2br(e($post->content)) !!}
                </div>
            </div>

            {{-- Footer nav --}}
            <div class="mt-8 flex items-center justify-between">
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
@endsection
