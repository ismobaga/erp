<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Crommix\Blog\BlogServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    BlogServiceProvider::class,
];
