@extends('crommix-blog::layouts.public')

@section('title', $page->seo_title ?: $page->title)
@section('meta_description', $page->seo_description ?: ($page->hero_subtitle ?: 'Page publique ' . ($blogCompany->name ?? 'CROMMIX')))
@section('canonical', route('blog.pages.show', $page->slug))

@section('content')
    <x-site.page-header eyebrow="{{ $blogCompany->name ?? 'CROMMIX' }}" :title="$page->hero_title ?: $page->title" :lead="$page->hero_subtitle" />

    <section class="site-container py-14 lg:py-20">
        <div class="mx-auto max-w-3xl">
            <div class="blog-content">
                {{ $page->renderedContent() }}
            </div>
        </div>
    </section>

    <x-site.cta title="Parlons de votre projet." text="Notre équipe vous répond et vous oriente vers la bonne solution." />
@endsection
