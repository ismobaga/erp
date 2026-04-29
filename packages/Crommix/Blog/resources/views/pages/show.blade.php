@extends('crommix-blog::layouts.public')

@section('title', $page->seo_title ?: $page->title)
@section('meta_description', $page->seo_description ?: 'Page publique Crommix Mali')

@push('styles')
    <style>
        .hero {
            padding: 84px 24px 72px;
            background:
                radial-gradient(520px 220px at 18% 5%, rgba(193, 236, 212, 0.45) 0%, transparent 60%),
                linear-gradient(145deg, #f5f3f3 5%, #efeded 55%, #e4e2e2 100%);
            color: #1b1c1c;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid #c1c8c2;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -90px;
            top: -60px;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: rgba(27, 67, 50, 0.08);
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(2rem, 3vw, 3rem);
            max-width: 780px;
            position: relative;
            z-index: 1;
            color: #012d1d;
            font-family: 'Manrope', sans-serif;
        }

        .hero p {
            margin: 0;
            color: #414844;
            font-size: 1.05rem;
            max-width: 760px;
            position: relative;
            z-index: 1;
        }

        .wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 22px;
        }

        .content {
            background: #fff;
            margin-top: -34px;
            border-radius: 20px;
            border: 1px solid #c1c8c2;
            padding: 28px;
            line-height: 1.82;
            box-shadow: 0 20px 38px rgba(27, 67, 50, 0.12);
        }

        .badge {
            display: inline-block;
            margin-bottom: 14px;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #e5e2e1;
            color: #1b4332;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-family: 'Manrope', sans-serif;
        }

        .content p {
            color: #414844;
        }
    </style>
@endpush

@section('content')
    <section class="hero">
        <div class="wrap">
            <h1>{{ $page->hero_title ?: $page->title }}</h1>
            @if(filled($page->hero_subtitle))
                <p>{{ $page->hero_subtitle }}</p>
            @endif
        </div>
    </section>
    <section class="wrap content">
        <span class="badge">Page publique</span>
        {!! nl2br(e($page->content)) !!}
    </section>
@endsection
