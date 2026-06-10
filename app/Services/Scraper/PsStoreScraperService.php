<?php

namespace App\Services\Scraper;

class PsStoreScraperService
{
    protected string $pythonPath;
    protected string $scriptPath;

    public function __construct()
    {
        $this->pythonPath = base_path('python/venv/bin/python');
        $this->scriptPath = base_path('python/ps_store_scraper.py');
    }

    public function scrape(string $url): array
    {
        $cmd = escapeshellcmd("{$this->pythonPath} {$this->scriptPath} " . escapeshellarg($url));
        exec($cmd, $output, $status);

        if ($status !== 0) {
            return [
                'error' => 'Python script failed',
                'details' => implode("\n", $output),
            ];
        }

        $jsonOutput = implode("\n", $output);

        $data = json_decode($jsonOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => 'Invalid JSON from Python',
                'raw' => $jsonOutput,
            ];
        }

        return $data;
    }
}
