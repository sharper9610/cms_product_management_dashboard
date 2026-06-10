<?php

namespace App\Services\Cms;

use App\Models\CurrencyConvertRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * App\Services\Cms\CurrencyExchange::getRate('BRL', 'EUR');
 */
class CurrencyExchange
{
    const BASE_CURRENCY = ['GBP', 'EUR', 'USD'];

    const HIGH_EXCHANGE_RATE_CURRENCIES = [
        'VND',
        'JPY',
        'KRW',
        'BYR',
        'IDR',
        'UZS',
        'COP',
        'CLP',
        'AMD',
        'CRC'
    ];

    public static function getRate(string $from, string $to, string $date = '')
    {
        $rate = 0;

        if ($date == '') {
            $date = date('Y-m-d');
        }

        if ($from == '' || $to == '') {
            return $rate;
        }

        $inverse = false;
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1;
        }

        if (in_array($from, self::BASE_CURRENCY)) {
            $currency = "$from-$to";
        } elseif (in_array($to, self::BASE_CURRENCY)) {
            $currency = "$to-$from";
            $inverse = true;
        } else {
            return static::otherExRate($from, $to, $date);
        }

        $value = static::getHistoricalRate($currency, $date);

        $maxLookback = 3;
        $daysChecked = 0;
        $originalDate = $date;
        while (is_null($value) && $daysChecked < $maxLookback) {
            $prevDay = static::getPreviousDay($date);
            $date = $prevDay;
            $value = static::getHistoricalRate($currency, $date);
            $daysChecked++;
            // dump($date);
        }

        if (is_null($value)) {
            Log::debug("Historical rate not found for $currency, starting from $originalDate, after checking $maxLookback days.");
        }

        if (is_numeric($value) && $value > 0) {
            $rate = $inverse ? (1 / $value) : $value;
        }

        if (static::requiresSixDecimalPlaces($from)) {
            return number_format($rate, 6, '.', '');
        }

        return round($rate, 3);
    }

    public static function getOldRate(string $from, string $to, string $date)
    {
        if ($from == '' || $to == '' || $date == '') {
            throw new \Exception("Invalid from|to|date");
        }

        return static::getRate($from, $to, $date);
    }

    private static function requiresSixDecimalPlaces(string $currency)
    {
        return in_array($currency, self::HIGH_EXCHANGE_RATE_CURRENCIES);
    }

    private static function otherExRate(string $from, string $to, string $date)
    {
        $fromEur = static::getRate('EUR', $from, $date);
        $toEur = static::getRate('EUR', $to, $date);

        if ($fromEur > 0) {
            return number_format($toEur / $fromEur, 6, '.', '');
        }

        return 1;
    }

    // private static function getHistoricalRate($currency, $date)
    // {
    //     if ($currency == '' || $date == '') {
    //         return 0;
    //     }

    //     return CurrencyConvertRate::where('currency', $currency)
    //         ->where('date', $date)
    //         ->value('rate') ?? 0;
    // }

    private static function getHistoricalRate($currency, $date)
    {
        if ($currency == '' || $date == '') {
            return 0;
        }

        return Cache::tags('old_ex_rate')->remember($currency.'_'.$date, Carbon::now()->addMinutes(1440),
            function() use ($currency, $date) {
                return CurrencyConvertRate::where('currency', $currency)
                    ->where('date', $date)->value('rate');
        });
    }

    private static function getPreviousDay(string $date)
    {
        $previousDayTimestamp = strtotime($date . ' -1 day');
        return date('Y-m-d', $previousDayTimestamp);
    }
}
