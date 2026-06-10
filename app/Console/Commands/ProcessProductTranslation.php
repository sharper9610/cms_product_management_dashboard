<?php

namespace App\Console\Commands;

use App\Services\Openai\TranslationService;
use Illuminate\Console\Command;

class ProcessProductTranslation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:translate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process translations for all products and show localization mappings';

    /**
     * Execute the console command.
     */
    public function handle(TranslationService $translationService)
    {
        $this->info('Processing products Translation...');
        $translationService->processProductTranslation();
        $this->info('✅ Translation completed successfully.');
    }
}
