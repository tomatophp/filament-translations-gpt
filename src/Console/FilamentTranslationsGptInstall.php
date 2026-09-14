<?php

namespace TomatoPHP\FilamentTranslationsGpt\Console;

use Illuminate\Console\Command;

class FilamentTranslationsGptInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'filament-translations-gpt:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'install package and publish assets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running migrations');
        $this->call('migrate', ['--force' => true]);
        $this->call('optimize:clear');
        $this->info('Filament translations gpt installed successfully.');

        return self::SUCCESS;
    }
}
