<?php

namespace Crommix\Blog\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Tiptap\Editor;

/**
 * Converts between the rich editor's document (TipTap JSON) and Markdown.
 *
 * Anything Markdown can't express (tables, aligned text, underline, custom
 * blocks…) is written as inline HTML, which Markdown allows, so a round trip
 * through the Markdown source keeps it.
 */
final class RichContentMarkdown
{
    public function __construct(private readonly Editor $editor) {}

    public static function toHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return self::wrapTightListItems(trim($converter->convert($markdown)->getContent()));
    }

    /**
     * Markdown "tight" lists render as <li>text<ul>…</ul></li>; the editor
     * expects list item text inside a paragraph (<li><p>text</p><ul>…</ul></li>).
     */
    private static function wrapTightListItems(string $html): string
    {
        if (! str_contains($html, '<li>')) {
            return $html;
        }

        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $blocks = ['p', 'ul', 'ol', 'pre', 'blockquote', 'table', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'div'];

        foreach (iterator_to_array($dom->getElementsByTagName('li')) as $li) {
            $paragraph = null;

            foreach (iterator_to_array($li->childNodes) as $child) {
                $isBlock = $child instanceof \DOMElement && in_array(strtolower($child->tagName), $blocks, true);

                if ($isBlock) {
                    $paragraph = null;

                    continue;
                }

                if ($child instanceof \DOMText && trim($child->textContent) === '') {
                    continue;
                }

                if ($paragraph === null) {
                    $paragraph = $dom->createElement('p');
                    $li->insertBefore($paragraph, $child);
                }

                $paragraph->appendChild($child);
            }
        }

        $root = $dom->getElementById('root');
        $out = '';

        foreach ($root->childNodes as $node) {
            $out .= $dom->saveHTML($node);
        }

        return $out;
    }

    /** @param  array<string, mixed>  $document */
    public function toMarkdown(array $document): string
    {
        $blocks = array_map(fn (array $node): string => $this->block($node), $document['content'] ?? []);

        return trim(implode("\n\n", array_filter($blocks, fn (string $block): bool => $block !== '')))."\n";
    }

    /** @param  array<string, mixed>  $node */
    private function block(array $node, string $indent = ''): string
    {
        $attrs = $node['attrs'] ?? [];

        if (! in_array($attrs['textAlign'] ?? 'start', [null, 'start', 'left'], true)) {
            return $this->rawHtml($node);
        }

        return match ($node['type']) {
            'paragraph' => $this->inline($node['content'] ?? []),
            'heading' => str_repeat('#', (int) ($attrs['level'] ?? 2)).' '.$this->inline($node['content'] ?? []),
            'blockquote' => $this->prefixLines($this->children($node), '> '),
            'bulletList' => $this->list($node, fn (): string => '- '),
            'orderedList' => $this->list($node, fn (int $i): string => ((int) ($attrs['start'] ?? 1) + $i).'. '),
            'codeBlock' => '```'.($attrs['language'] ?? '')."\n".$this->plainText($node)."\n```",
            'horizontalRule' => '---',
            'image' => $this->image($attrs),
            default => $this->rawHtml($node),
        };
    }

    /** @param  array<string, mixed>  $node */
    private function children(array $node): string
    {
        return implode("\n\n", array_map(fn (array $child): string => $this->block($child), $node['content'] ?? []));
    }

    /** @param  array<string, mixed>  $node */
    private function list(array $node, callable $marker): string
    {
        $items = [];

        foreach (array_values($node['content'] ?? []) as $i => $item) {
            $prefix = $marker($i);
            $body = $this->children($item);
            $pad = str_repeat(' ', strlen($prefix));
            $items[] = $prefix.$this->prefixLines($body, $pad, skipFirst: true);
        }

        return implode("\n", $items);
    }

    /** @param  array<int, array<string, mixed>>  $nodes */
    private function inline(array $nodes): string
    {
        $out = '';

        foreach ($nodes as $node) {
            $out .= match ($node['type']) {
                'text' => $this->text($node),
                'hardBreak' => "  \n",
                'image' => $this->image($node['attrs'] ?? []),
                default => $this->rawHtml($node, inline: true),
            };
        }

        return $out;
    }

    /** @param  array<string, mixed>  $node */
    private function text(array $node): string
    {
        $marks = collect($node['marks'] ?? [])->keyBy('type');
        $text = (string) ($node['text'] ?? '');

        if ($marks->has('code')) {
            $text = '`'.$text.'`';
        } else {
            $text = $this->escape($text);
        }

        // Marks Markdown has no syntax for keep their HTML form.
        foreach (['underline' => 'u', 'subscript' => 'sub', 'superscript' => 'sup', 'highlight' => 'mark'] as $mark => $tag) {
            if ($marks->has($mark)) {
                $text = "<{$tag}>{$text}</{$tag}>";
            }
        }

        if ($marks->has('strike')) {
            $text = '~~'.$text.'~~';
        }
        if ($marks->has('italic')) {
            $text = '*'.$text.'*';
        }
        if ($marks->has('bold')) {
            $text = '**'.$text.'**';
        }
        if ($marks->has('link')) {
            $href = (string) ($marks->get('link')['attrs']['href'] ?? '');
            $text = '['.$text.']('.str_replace([' ', ')'], ['%20', '%29'], $href).')';
        }

        return $text;
    }

    /** @param  array<string, mixed>  $attrs */
    private function image(array $attrs): string
    {
        // Attachments are referenced by id; keep the HTML so the id survives.
        if (filled($attrs['id'] ?? null)) {
            return $this->rawHtml(['type' => 'image', 'attrs' => $attrs], inline: true);
        }

        return '!['.$this->escape((string) ($attrs['alt'] ?? '')).']('.($attrs['src'] ?? '').')';
    }

    /** @param  array<string, mixed>  $node */
    private function plainText(array $node): string
    {
        return implode('', array_map(
            fn (array $child): string => $child['type'] === 'text' ? (string) $child['text'] : "\n",
            $node['content'] ?? [],
        ));
    }

    /** @param  array<string, mixed>  $node */
    private function rawHtml(array $node, bool $inline = false): string
    {
        $content = $inline ? [['type' => 'paragraph', 'content' => [$node]]] : [$node];
        $html = (string) $this->editor->setContent(['type' => 'doc', 'content' => $content])->getHtml();

        if ($inline) {
            $html = (string) preg_replace('#^<p>(.*)</p>$#s', '$1', $html);
        }

        return $html;
    }

    private function escape(string $text): string
    {
        $text = (string) preg_replace('/([\\\\`*_\[\]<>])/', '\\\\$1', $text);

        // A line starting like a heading, quote or list item must not become one.
        $text = (string) preg_replace('/^(\s*)([#>+\-])(\s)/', '$1\\\\$2$3', $text);

        return (string) preg_replace('/^(\s*\d+)\.(\s)/', '$1\\\\.$2', $text);
    }

    private function prefixLines(string $text, string $prefix, bool $skipFirst = false): string
    {
        $lines = explode("\n", $text);

        foreach ($lines as $i => $line) {
            if ($skipFirst && $i === 0) {
                continue;
            }
            $lines[$i] = $line === '' ? rtrim($prefix) : $prefix.$line;
        }

        return implode("\n", $lines);
    }
}
