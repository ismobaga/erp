<?php

namespace Crommix\Blog\Filament\Concerns;

use Crommix\Blog\Support\BlogContent;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared behaviour for the blog panel resources: company-feature gating,
 * the rich editor setup and per-company unique slugs.
 */
trait BlogResourceHelpers
{
    public static function canViewAny(): bool
    {
        return auth()->check() && company_feature_enabled('blog');
    }

    protected static function richContentEditor(string $field, string $label): RichEditor
    {
        return RichEditor::make($field)
            ->label($label)
            ->required()
            ->fileAttachmentsDisk(BlogContent::disk())
            ->fileAttachmentsDirectory('blog/attachments')
            ->fileAttachmentsVisibility('public')
            ->extraInputAttributes(['style' => 'min-height: 24rem;']);
    }

    /** Slug input unique within the current company only. */
    protected static function companySlugInput(string $helperText): TextInput
    {
        return TextInput::make('slug')
            ->label('Slug URL')
            ->required()
            ->maxLength(255)
            ->alphaDash()
            ->unique(
                ignoreRecord: true,
                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('company_id', currentCompany()?->getKey()),
            )
            ->helperText($helperText);
    }
}
