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
        :root {
            --bg: #f5f7fb;
            --ink: #0b1c30;
            --muted: #4e5a70;
            --primary: #002045;
            --line: #c8d6ef;
            --accent: #8df5e4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            background: radial-gradient(900px 420px at 90% -20%, #dce9ff 0%, transparent 55%), var(--bg);
            color: var(--ink);
        }

        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 22px 46px;
        }

        .back {
            display: inline-flex;
            margin-bottom: 16px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        article {
            background: #fff;
            border: 1px solid var(--line);
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
            color: var(--primary);
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
            color: var(--muted);
            margin: 0 0 18px;
        }

        .content {
            line-height: 1.8;
            color: #1b2a43;
        }

        .divider {
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, #43af9f, var(--accent), transparent);
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <div class="wrap">
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
    </div>
</body>

</html>