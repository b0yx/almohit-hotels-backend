<?php

namespace App\Services;

use App\Models\Currency;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Symfony\Component\Intl\Currencies;

class CurrencyService
{
    public const ISO_4217_CODES = [
        'AED', 'AUD', 'BHD', 'CAD', 'CHF', 'CNY', 'EGP', 'EUR', 'GBP', 'INR',
        'JOD', 'JPY', 'KWD', 'OMR', 'PKR', 'QAR', 'SAR', 'TRY', 'USD', 'YER',
    ];

    public function __construct(private ExchangeRateService $exchangeRates)
    {
    }

    public function convert(string|float|int $amount, Currency|string $from, Currency|string $to, CarbonInterface|string|null $at = null): string
    {
        $toCurrency = $this->resolveCurrency($to);
        $rate = $this->exchangeRates->rate($from, $toCurrency, $at);
        $converted = bcmul((string) $amount, $rate, 10);

        return $this->round($converted, $toCurrency);
    }

    public function latestExchangeRate(Currency|string $from, Currency|string $to): string
    {
        return $this->exchangeRates->rate($from, $to);
    }

    public function historicalExchangeRate(Currency|string $from, Currency|string $to, CarbonInterface|string $at): string
    {
        return $this->exchangeRates->rate($from, $to, $at);
    }

    public function format(string|float|int $amount, Currency|string $currency, string $locale = 'en'): string
    {
        $model = $this->resolveCurrency($currency);
        $rounded = $this->round((string) $amount, $model);
        $number = number_format((float) $rounded, $model->decimal_places, $model->decimal_separator, $model->thousand_separator);

        if (str_starts_with(strtolower($locale), 'ar')) {
            $number = strtr($number, [
                '0' => '٠',
                '1' => '١',
                '2' => '٢',
                '3' => '٣',
                '4' => '٤',
                '5' => '٥',
                '6' => '٦',
                '7' => '٧',
                '8' => '٨',
                '9' => '٩',
                ',' => '٬',
                '.' => '٫',
            ]);
        }

        return $model->symbol_position === Currency::SYMBOL_AFTER
            ? trim($number.' '.$model->symbol)
            : trim($model->symbol.$number);
    }

    public function validateCurrencyCode(string $code): bool
    {
        $normalized = strtoupper($code);

        if (class_exists(Currencies::class)) {
            return Currencies::exists($normalized);
        }

        return in_array($normalized, self::ISO_4217_CODES, true);
    }

    public function round(string|float|int $amount, Currency|string $currency): string
    {
        $model = $this->resolveCurrency($currency);

        if (class_exists(\Brick\Math\BigDecimal::class)) {
            return \Brick\Math\BigDecimal::of((string) $amount)
                ->toScale($model->decimal_places, \Brick\Math\RoundingMode::HalfUp);
        }

        return number_format(round((float) $amount, $model->decimal_places), $model->decimal_places, '.', '');
    }

    public function resolveCurrency(Currency|string $currency): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        $code = strtoupper($currency);
        $model = Currency::query()->where('code', $code)->first();

        if (! $model) {
            throw new InvalidArgumentException("Currency {$code} was not found.");
        }

        return $model;
    }
}
