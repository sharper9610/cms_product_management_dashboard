<?php

namespace App\Http\Controllers;

use App\Services\Scraper\PsStoreScraperService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PsStoreController extends Controller
{
    protected PsStoreScraperService $scraper;

    public function __construct(PsStoreScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    public function scrape(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['error' => 'Missing url parameter'], 400);
        }

        $data = $this->scraper->scrape($url);

        $price = $data['price_data'] ?? [];

        $formatted = [
            'price_data' => [
                'final_price' => $this->parsePrice($price['final_price'] ?? null),
                'original_price' => $this->parsePrice($price['original_price'] ?? null),
                'discount_percent' => $this->parseDiscount($price['discount_percent'] ?? null),
                'discount_deadline' => $this->parseDate($price['discount_deadline'] ?? null),
                'discount_deadline_unix' => $this->parseDateToUnix($price['discount_deadline'] ?? null),
                'lowest_recent_price' => $this->parsePrice($price['lowest_recent_price'] ?? null),
            ]
        ];

        return response()->json($formatted, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function parsePrice($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,3}(?:\.\d{3})*(?:,\d{1,2})?)/', $str, $matches)) {
            $num = str_replace('.', '', $matches[1]);
            $num = str_replace(',', '.', $num);
            return floatval($num);
        }

        return null;
    }

    private function parseDiscount($str)
    {
        if (!$str) return null;
        preg_match('/(\d+)/', $str, $matches);
        return isset($matches[1]) ? (int)$matches[1] : null;
    }

    private function parseDate($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4}\s\d{1,2}:\d{2}\s?(?:AM|PM)?)/i', $str, $matches)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y h:i A', $matches[1], 'UTC');
                return $dt->format('d/m/y g:i A');
            } catch (\Exception $e) {
                return $matches[1];
            }
        }

        return $str;
    }

    private function parseDateToUnix($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4}\s\d{1,2}:\d{2}\s?(?:AM|PM)?)/i', $str, $matches)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y h:i A', $matches[1], 'UTC');

                $dt->setTimezone('Europe/Stockholm');

                return $dt->timestamp;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
