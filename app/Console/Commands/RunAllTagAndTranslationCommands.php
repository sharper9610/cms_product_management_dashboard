<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunAllTagAndTranslationCommands extends Command
{
    protected $signature = 'all:run-tags-and-translations';
    protected $description = 'Run all SEO, genre, franchise, and system requirements translation commands sequentially';

    public function handle()
    {
        $this->info('Running SEO tags command...');
        $this->call('seo:generate-tags-and-store');
        sleep(300);

        $this->info('Running Genre tags command...');
        $this->call('genre:generate-tags-and-store');
        sleep(300);

        $this->info('Running Franchise tags command...');
        $this->call('francise:generate-tags-and-store');
        sleep(300);

        $this->info('Running System Requirements Translation command...');
        $this->call('product:system-requirements-translation');
        sleep(300);

        $this->info('All commands executed successfully.');
    }
}
