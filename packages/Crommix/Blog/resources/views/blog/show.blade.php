@extends('crommix-blog::layouts.public')

@section('title', $post->seo_title ?: $post->title)
@section('meta_description', $post->seo_description ?: 'Article de blog Crommix Mali')

@push('styles')
    <style>
        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 22px 46px;
        }

        .back {
            display: inline-flex;
            margin-bottom: 16px;
            color: #1b4332;
            text-decoration: none;
            font-weight: 700;
            font-family: 'Manrope', sans-serif;
        }

        article {
            background: #fff;
            border: 1px solid #c1c8c2;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 16px 34px rgba(27, 67, 50, 0.1);
        }

        .kicker {
            display: inline-block;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #e5e2e1;
            color: #1b4332;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 700;
            margin-bottom: 12px;
            font-family: 'Manrope', sans-serif;
        }

        h1 {
            margin: 0 0 10px;
            font-size: clamp(1.6rem, 2.7vw, 2.5rem);
            color: #012d1d;
            font-family: 'Manrope', sans-serif;
        }

        .meta {
            font-size: 13px;
            color: #5f5e5e;
            margin: 0 0 18px;
            font-family: 'Manrope', sans-serif;
        }

        .content {
            line-height: 1.8;
            color: #414844;
        }

        .divider {
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, #1b4332, #86af99, transparent);
            margin-bottom: 18px;
        }
    </style>
@endpush

@section('content')
    <main class="wrap">
        <a class="back" href="{{ route('blog.index') }}">← Retour au blog</a>
        <article>
            <span class="kicker">Article</span>
            <h1>{{ $post->title }}</h1>
            <div class="divider"></div>
            <p class="meta">
                {{ optional($post->published_at)->format('d/m/Y H:i') ?? 'Non planifié' }}
                @if($post->author)
                    · {{ $post->author->name }}
                @endif
            </p>
            <div class="content">{!! nl2br(e($post->content)) !!}</div>
        </article>
    </main>
@endsection
