<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Blog</title>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            margin: 0;
            background: #f7f7fb;
            color: #1b1d29;
        }

        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e6e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        a {
            color: #0f4ccf;
            text-decoration: none;
        }

        h1 {
            margin-top: 0;
        }

        .meta {
            font-size: 13px;
            color: #59607a;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <h1>Blog</h1>

        @forelse($posts as $post)
            <article class="card">
                <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                <p class="meta">
                    {{ optional($post->published_at)->format('d/m/Y H:i') ?? 'Non planifie' }}
                    @if($post->author)
                        · {{ $post->author->name }}
                    @endif
                </p>
                @if(filled($post->excerpt))
                    <p>{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <p>Aucun article publie pour le moment.</p>
        @endforelse

        {{ $posts->links() }}
    </div>
</body>

</html>