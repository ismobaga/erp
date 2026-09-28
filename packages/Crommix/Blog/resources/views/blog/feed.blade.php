{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $blogCompany->name }} — Blog</title>
        <link>{{ route('blog.index') }}</link>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
        <description>{{ $blogCompany->slogan ?: 'Articles et actualités de '.$blogCompany->name }}</description>
        <language>fr</language>
@if($posts->isNotEmpty())
        <lastBuildDate>{{ $posts->first()->publicDate()?->toRssString() }}</lastBuildDate>
@endif
@foreach($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('blog.show', $post->slug) }}</link>
            <guid isPermaLink="true">{{ route('blog.show', $post->slug) }}</guid>
            <pubDate>{{ $post->publicDate()?->toRssString() }}</pubDate>
@if($post->category)
            <category>{{ $post->category->name }}</category>
@endif
            <description>{{ $post->summary(300) }}</description>
        </item>
@endforeach
    </channel>
</rss>
