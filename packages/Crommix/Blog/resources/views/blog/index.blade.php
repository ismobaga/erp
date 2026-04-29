@extends('crommix-blog::layouts.public')

@section('title', 'CGL · Blog')
@section('meta_description', 'Articles opérationnels, retours terrain et stratégies de croissance.')

@push('styles')
    <style>
        .hero {
            background: radial-gradient(560px 260px at 15% 0%, rgba(193, 236, 212, .5) 0%, transparent 62%), linear-gradient(145deg, #f5f3f3 15%, #efeded 70%, #e4e2e2 100%);
            color: #1b1c1c;
            padding: 72px 22px 54px;
            border-bottom: 1px solid #c1c8c2;
        }

        .hero-wrap,
        .list-wrap {
            max-width: 1040px;
            margin: 0 auto;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.8rem, 2.8vw, 2.8rem);
            font-family: 'Manrope', sans-serif;
            color: #012d1d;
        }

        .hero p {
            margin: 0;
            color: #414844;
            max-width: 760px;
        }

        .list {
            margin: -22px auto 38px;
            padding: 0 22px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #c1c8c2;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 14px;
            box-shadow: 0 14px 30px rgba(27, 67, 50, 0.09);
        }

        .eyebrow {
            display: inline-block;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #e5e2e1;
            color: #1b4332;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Manrope', sans-serif;
        }

        .title {
            margin: 0 0 8px;
            font-size: 1.3rem;
            font-family: 'Manrope', sans-serif;
        }

        .title a {
            color: #012d1d;
            text-decoration: none;
        }

        .title a:hover {
            color: #1b4332;
        }

        .meta {
            margin: 0 0 12px;
            color: #5f5e5e;
            font-size: 13px;
            font-family: 'Manrope', sans-serif;
        }

        .excerpt {
            margin: 0;
            line-height: 1.7;
            color: #414844;
        }

        .empty {
            border: 1px dashed #c1c8c2;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            color: #5f5e5e;
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
            <h1>Journal CGL</h1>
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