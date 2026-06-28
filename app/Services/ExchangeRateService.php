<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ExchangeRateService
{
    public function latest(Currency|string $from, Currency|string $to): ?ExchangeRate
    {
        [$fromCurrency, $toCurrency] = $this->resolvePair($from, $to);

        if ($fromCurrency->id === $toCurrency->id) {
            return null;
        }

        return $this->basePairQuery($fromCurrency, $toCurrency)
            ->where('effective_from', '<=', now())
            ->latest('effective_from')
            ->first();
    }

    public function historical(Currency|string $from, Currency|string $to, CarbonInterface|string $at): ?ExchangeRate
    {
        [$fromCurrency, $toCurrency] = $this->resolvePair($from, $to);
        $effectiveAt = is_string($at) ? now()->parse($at) : $at;

        if ($fromCurrency->id === $toCurrency->id) {
            return null;
        }

        return $this->basePairQuery($fromCurrency, $toCurrency)
            ->where('effective_from', '<=', $effectiveAt)
            ->where(fn (Builder $query) => $query
                ->whereNull('effective_to')
                ->orWhere('effective_to', '>', $effectiveAt))
            ->latest('effective_from')
            ->first();
    }

    public function rate(Currency|string $from, Currency|string $to, CarbonInterface|string|null $at = null): string
    {
        [$fromCurrency, $toCurrency] = $this->resolvePair($from, $to);

        if ($fromCurrency->id === $toCurrency->id) {
            return '1';
        }

        $direct = $at
            ? $this->historical($fromCurrency, $toCurrency, $at)
            : $this->latest($fromCurrency, $toCurrency);

        if ($direct) {
            return (string) $direct->exchange_rate;
        }

        $inverse = $at
            ? $this->historical($toCurrency, $fromCurrency, $at)
            : $this->latest($toCurrency, $fromCurrency);

        if ($inverse) {
            return bcdiv('1', (string) $inverse->exchange_rate, 10);
        }

        return $this->crossRate($fromCurrency, $toCurrency, $at);
    }

    public function crossRate(Currency|string $from, Currency|string $to, CarbonInterface|string|null $at = null): string
    {
        [$fromCurrency, $toCurrency] = $this->resolvePair($from, $to);
        $baseCurrency = Currency::query()->where('is_default', true)->first();

        if (! $baseCurrency) {
            throw new InvalidArgumentException('Default currency is not configured.');
        }

        if ($fromCurrency->id === $baseCurrency->id || $toCurrency->id === $baseCurrency->id) {
            throw new InvalidArgumentException("Exchange rate {$fromCurrency->code} to {$toCurrency->code} was not found.");
        }

        $fromToBase = $this->rate($fromCurrency, $baseCurrency, $at);
        $baseToTo = $this->rate($baseCurrency, $toCurrency, $at);

        return bcmul($fromToBase, $baseToTo, 10);
    }

    public function validatePair(Currency $fromCurrency, Currency $toCurrency): void
    {
        if (! $fromCurrency->is_active || ! $toCurrency->is_active) {
            throw new InvalidArgumentException('Both currencies must be active.');
        }

        if ($fromCurrency->id === $toCurrency->id) {
            throw new InvalidArgumentException('From and to currencies must be different.');
        }
    }

    private function basePairQuery(Currency $fromCurrency, Currency $toCurrency): Builder
    {
        return ExchangeRate::query()
            ->with(['fromCurrency', 'toCurrency'])
            ->where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id);
    }

    private function resolvePair(Currency|string $from, Currency|string $to): array
    {
        return [$this->resolveCurrency($from), $this->resolveCurrency($to)];
    }

    private function resolveCurrency(Currency|string $currency): Currency
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
