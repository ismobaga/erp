<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-mail ou téléphone')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->placeholder('Entrez votre e-mail ou votre numéro de téléphone');
    }
}