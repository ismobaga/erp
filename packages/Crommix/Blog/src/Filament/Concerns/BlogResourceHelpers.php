<?php

namespace Crommix\Blog\Filament\Concerns;

use Crommix\Blog\Support\BlogContent;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared behaviour for the blog panel resources: publishing rights, the rich
 * editor setup and per-company unique slugs. Access control itself (blog.*
 * permissions + the "blog" company feature) comes from HasPermissionAccess.
 */
trait BlogResourceHelpers
{
    /** Whether the user may publish, schedule or feature content (blog.publish). */
    public static function canPublish(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->can('blog.publish');
    }

    /** Published content can only be changed by someone allowed to publish. */
    protected static function canChangeRecord(Model $record, string $action): bool
    {
        if (! static::canAccessPermission($action)) {
            return false;
        }

        return $record->getAttribute('status') !== 'published' || static::canPublish();
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
