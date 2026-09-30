<?php

namespace Crommix\Blog\Filament\Actions;

use Crommix\Blog\Support\RichContentMarkdown;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\StateCasts\RichEditorStateCast;
use Filament\Support\Icons\Heroicon;

/**
 * Hint actions on a RichEditor to edit its content as raw HTML or Markdown.
 *
 * The edited source is parsed back into the editor's document, so markup the
 * editor doesn't support (scripts, iframes, arbitrary classes…) is dropped,
 * exactly as if it had been pasted into the editor.
 */
final class EditSourceAction
{
    public static function html(): Action
    {
        return Action::make('editHtmlSource')
            ->label('HTML')
            ->icon(Heroicon::OutlinedCodeBracket)
            ->modalHeading('Modifier la source HTML')
            ->modalDescription('Les balises non prises en charge par l’éditeur (scripts, iframes, classes…) seront retirées.')
            ->modalSubmitActionLabel('Appliquer')
            ->modalWidth('5xl')
            ->fillForm(fn (RichEditor $component): array => ['source' => self::currentHtml($component)])
            ->schema([
                CodeEditor::make('source')
                    ->hiddenLabel()
                    ->language(Language::Html),
            ])
            ->action(fn (array $data, RichEditor $component) => self::apply($component, (string) ($data['source'] ?? '')));
    }

    public static function markdown(): Action
    {
        return Action::make('editMarkdownSource')
            ->label('Markdown')
            ->icon(Heroicon::OutlinedHashtag)
            ->modalHeading('Modifier en Markdown')
            ->modalDescription('Markdown GitHub (titres, listes, liens, tableaux, blocs de code). Ce qui n’a pas d’équivalent Markdown reste en HTML.')
            ->modalSubmitActionLabel('Appliquer')
            ->modalWidth('5xl')
            ->fillForm(fn (RichEditor $component): array => [
                'source' => (new RichContentMarkdown($component->getTipTapEditor()))->toMarkdown(self::currentDocument($component)),
            ])
            ->schema([
                CodeEditor::make('source')
                    ->hiddenLabel()
                    ->language(Language::Markdown),
            ])
            ->action(fn (array $data, RichEditor $component) => self::apply(
                $component,
                RichContentMarkdown::toHtml((string) ($data['source'] ?? '')),
            ));
    }

    /** @return array<string, mixed> */
    private static function currentDocument(RichEditor $component): array
    {
        $state = $component->getState();

        if (is_array($state)) {
            return $state;
        }

        return $component->getTipTapEditor()->setContent($state ?: '<p></p>')->getDocument();
    }

    private static function currentHtml(RichEditor $component): string
    {
        $html = (string) $component->getTipTapEditor()->setContent(self::currentDocument($component))->getHtml();

        return self::prettify($html);
    }

    private static function apply(RichEditor $component, string $html): void
    {
        $document = app(RichEditorStateCast::class, ['richEditor' => $component])->set($html);

        $component->state($document);
    }

    /** Put each block element on its own line so the source is readable. */
    private static function prettify(string $html): string
    {
        // Default table-cell attributes are noise in the source view.
        $html = str_replace([' rowspan="1"', ' colspan="1"'], '', $html);

        return trim((string) preg_replace(
            '#(</(?:p|h[1-6]|ul|ol|li|blockquote|pre|table|tr|figure)>|<hr\s*/?>)#i',
            "$1\n",
            $html,
        ));
    }
}
