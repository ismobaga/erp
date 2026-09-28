<?php

namespace Crommix\Blog\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Renders stored blog content as safe HTML.
 *
 * Content written with the rich editor is HTML and goes through Filament's
 * renderer (resolves attachment URLs) and HTML sanitizer. Legacy content
 * saved from the old plain textarea has no block markup, so it is escaped and its
 * line breaks preserved, exactly as before.
 */
final class BlogContent
{
    public static function toHtml(?string $content): HtmlString
    {
        $content = (string) $content;

        if (trim($content) === '') {
            return new HtmlString('');
        }

        if (! self::containsMarkup($content)) {
            return new HtmlString(nl2br(e($content)));
        }

        $html = RichContentRenderer::make($content)
            ->fileAttachmentsDisk(self::disk())
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        return new HtmlString($html);
    }

    public static function toText(?string $content): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $content);
        // Keep words from adjacent blocks apart ("<h2>A</h2><p>B</p>" → "A B").
        $html = (string) preg_replace('#</?(p|h[1-6]|li|ul|ol|div|blockquote|figure|figcaption|table|tr|td|th|pre)\b[^>]*>|<br\s*/?>#i', ' ', $html);
        $html = strip_tags($html);
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function readingMinutes(?string $content, int $wordsPerMinute = 220): int
    {
        $words = str_word_count(Str::ascii(self::toText($content)));

        return max(1, (int) ceil($words / $wordsPerMinute));
    }

    public static function disk(): string
    {
        return (string) config('crommix-blog.disk', 'public');
    }

    private static function containsMarkup(string $content): bool
    {
        // The rich editor always wraps text in block elements; legacy textarea
        // content may contain a stray inline tag but never block markup.
        return preg_match('/<(p|h[1-6]|ul|ol|li|div|blockquote|figure|table|pre|hr|br|img)\b/i', $content) === 1;
    }
}
