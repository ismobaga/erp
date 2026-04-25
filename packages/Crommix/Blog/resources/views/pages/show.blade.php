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
        :root {
            --bg: #f3f7ff;
            --ink: #0b1c30;
            --muted: #4f6180;
            --primary: #002045;
            --primary-soft: #dce9ff;
            --line: #bdd0f1;
            --accent: #43af9f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            margin: 0;
            color: var(--ink);
            background: linear-gradient(180deg, #f7fbff 0%, var(--bg) 60%, #eef5ff 100%);
        }

        .hero {
            padding: 84px 24px 72px;
            background:
                radial-gradient(520px 220px at 18% 5%, rgba(141, 245, 228, 0.32) 0%, transparent 60%),
                linear-gradient(145deg, #001736 5%, #002a58 55%, #193c66 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -90px;
            top: -60px;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: rgba(220, 233, 255, 0.15);
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(2rem, 3vw, 3rem);
            max-width: 780px;
            position: relative;
            z-index: 1;
        }

        .hero p {
            margin: 0;
            color: #c6d8f7;
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
            border: 1px solid var(--line);
            padding: 28px;
            line-height: 1.82;
            box-shadow: 0 22px 42px rgba(0, 32, 69, 0.12);
        }

        .badge {
            display: inline-block;
            margin-bottom: 14px;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 700;
        }

        .content p {
            color: #20314d;
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
        <span class="badge">Page publique</span>
        {!! nl2br(e($page->content)) !!}
    </section>
</body>

</html>