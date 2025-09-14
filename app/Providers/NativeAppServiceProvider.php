<?php

namespace App\Providers;

use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\MenuBar;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->id('main')
            ->url('/')
            ->title('Laravel Valet Manager')
            ->width(1000)
            ->height(700)
            ->minWidth(800)
            ->minHeight(600);
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
