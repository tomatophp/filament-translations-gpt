<?php

namespace TomatoPHP\FilamentTranslationsGpt;

use Illuminate\Support\ServiceProvider;
use TomatoPHP\FilamentTranslationsGpt\Console\FilamentTranslationsGptInstall;

class FilamentTranslationsGptServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register generate command
        $this->commands([
            FilamentTranslationsGptInstall::class,
        ]);

        // Register Config file
        $this->mergeConfigFrom(__DIR__ . '/../config/filament-translations-gpt.php', 'filament-translations-gpt');

        // Publish Config
        $this->publishes([
            __DIR__ . '/../config/filament-translations-gpt.php' => config_path('filament-translations-gpt.php'),
        ], 'filament-translations-gpt-config');

        // Register Langs
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-translations-gpt');

        // Publish Lang
        $this->publishes([
            __DIR__ . '/../resources/lang' => base_path('lang/vendor/filament-translations-gpt'),
        ], 'filament-translations-gpt-lang');

    }

    public function boot(): void
    {
        // you boot methods here
    }
}
