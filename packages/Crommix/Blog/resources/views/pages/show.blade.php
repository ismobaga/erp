<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $page->seo_title ?: $page->title }}</title>
    @if(filled($page->seo_description))
        <meta name="description" content="{{ $page->seo_description }}" />
    @endif
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            margin: 0;
            background: #faf9f5;
            color: #1e1b17;
        }

        .hero {
            padding: 56px 24px;
            background: linear-gradient(135deg, #ebe1d6, #f4efe9);
        }

        .hero h1 {
            margin: 0 0 12px 0;
        }

        .wrap {
            max-width: 920px;
            margin: 0 auto;
        }

        .content {
            background: #fff;
            margin-top: -24px;
            border-radius: 14px;
            border: 1px solid #e8dfd4;
            padding: 24px;
            line-height: 1.7;
        }
    </style>
</head>

<body>
    <section class="hero">
        <div class="wrap">
            <h1>{{ $page->hero_title ?: $page->title }}</h1>
            @if(filled($page->hero_subtitle))
                <p>{{ $page->hero_subtitle }}</p>
            @endif
        </div>
    </section>
    <section class="wrap content">
        {!! nl2br(e($page->content)) !!}
    </section>
</body>

</html>