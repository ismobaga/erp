<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $post->seo_title ?: $post->title }}</title>
    @if(filled($post->seo_description))
        <meta name="description" content="{{ $post->seo_description }}" />
    @endif
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            margin: 0;
            background: #f7f7fb;
            color: #1b1d29;
        }

        .wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        article {
            background: #fff;
            border: 1px solid #e6e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        a {
            color: #0f4ccf;
            text-decoration: none;
        }

        .meta {
            font-size: 13px;
            color: #59607a;
        }

        .content {
            line-height: 1.65;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <p><a href="{{ route('blog.index') }}">← Retour au blog</a></p>
        <article>
            <h1>{{ $post->title }}</h1>
            <p class="meta">
                {{ optional($post->published_at)->format('d/m/Y H:i') ?? 'Non planifie' }}
                @if($post->author)
                    · {{ $post->author->name }}
                @endif
            </p>
            <div class="content">{!! nl2br(e($post->content)) !!}</div>
        </article>
    </div>
</body>

</html>