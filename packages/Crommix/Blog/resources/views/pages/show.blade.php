@extends('crommix-blog::layouts.public')

@section('title', $page->seo_title ?: $page->title)
@section('meta_description', $page->seo_description ?: ($page->hero_subtitle ?: 'Page publique ' . ($blogCompany->name ?? 'CROMMIX')))
@section('canonical', route('blog.pages.show', $page->slug))

@section('content')
    {{-- Hero --}}
    <section class="bg-[#f8f9ff] py-20">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            <span class="inline-block rounded-full bg-[#dce9ff] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2d476f]">Page publique</span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-[#002045] lg:text-5xl">
                {{ $page->hero_title ?: $page->title }}
            </h1>
            @if(filled($page->hero_subtitle))
                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-[#43474e]">{{ $page->hero_subtitle }}</p>
            @endif
        </div>
    </section>

    {{-- Content --}}
    <section class="bg-[#eff4ff] py-16">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-[#dce9ff] lg:p-12">
                <div class="blog-content">
                    {{ $page->renderedContent() }}
                </div>
            </div>

            <div class="mt-8">
                <a href="{{ route('company.contact') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-[#002045] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                    Nous contacter →
                </a>
            </div>
        </div>
    </section>
@endsection
