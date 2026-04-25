@extends('crommix-blog::layouts.public')

@section('title', $post->seo_title ?: $post->title)
@section('meta_description', $post->seo_description ?: 'Article de blog Crommix Forge')

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
            color: #002045;
            text-decoration: none;
            font-weight: 700;
        }

        article {
            background: #fff;
            border: 1px solid #c8d6ef;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(0, 32, 69, 0.1);
        }

        .kicker {
            display: inline-block;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #dce9ff;
            color: #002045;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: clamp(1.6rem, 2.7vw, 2.5rem);
        }

        .meta {
            font-size: 13px;
            color: #4e5a70;
            margin: 0 0 18px;
        }

        .content {
            line-height: 1.8;
            color: #1b2a43;
        }

        .divider {
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, #43af9f, #8df5e4, transparent);
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
