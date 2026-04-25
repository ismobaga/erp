@extends('crommix-blog::layouts.public')

@section('title', 'Crommix Forge · Blog')
@section('meta_description', 'Articles opérationnels, retours terrain et stratégies de croissance.')

@push('styles')
    <style>
        .hero {
            background: linear-gradient(140deg, #001838, #002c60 60%, #1a365d 100%);
            color: #fff;
            padding: 72px 22px 48px;
            border-bottom: 6px solid #8df5e4;
        }

        .hero-wrap,
        .list-wrap {
            max-width: 1040px;
            margin: 0 auto;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.8rem, 2.8vw, 2.8rem);
        }

        .hero p {
            margin: 0;
            color: #c5d8f7;
            max-width: 760px;
        }

        .list {
            margin: -22px auto 38px;
            padding: 0 22px;
        }

        .card {
            background: #fff;
            border: 1px solid #c8d6ef;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 14px;
            box-shadow: 0 16px 32px rgba(0, 32, 69, 0.08);
        }

        .eyebrow {
            display: inline-block;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #dce9ff;
            color: #002045;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .title {
            margin: 0 0 8px;
            font-size: 1.3rem;
        }

        .title a {
            color: #002045;
            text-decoration: none;
        }

        .title a:hover {
            color: #1a365d;
        }

        .meta {
            margin: 0 0 12px;
            color: #4e5a70;
            font-size: 13px;
        }

        .excerpt {
            margin: 0;
            line-height: 1.7;
            color: #23324a;
        }

        .empty {
            border: 1px dashed #c8d6ef;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            color: #4e5a70;
            background: #fff;
        }

        nav[role="navigation"] {
            margin-top: 18px;
        }
    </style>
@endpush

@section('content')
    <header class="hero">
        <div class="hero-wrap">
            <h1>Journal Crommix Forge</h1>
            <p>Articles opérationnels, retours terrain et stratégies de croissance pour structurer vos opérations.</p>
        </div>
    </header>

    <main class="list list-wrap">
        @forelse($posts as $post)
            <article class="card">
                <span class="eyebrow">Article</span>
                <h2 class="title"><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                <p class="meta">
                    {{ optional($post->published_at)->format('d/m/Y H:i') ?? 'Non planifié' }}
                    @if($post->author)
                        · {{ $post->author->name }}
                    @endif
                </p>
                @if(filled($post->excerpt))
                    <p class="excerpt">{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <section class="empty">Aucun article publié pour le moment.</section>
        @endforelse

        {{ $posts->links() }}
    </main>
@endsection
