<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ConvertsCurrency
{
    protected bool $exchangeRateConversion = false;

    protected static array $currenciesAttributes = ['target_currency_id', 'source_currency_id', 'exchange_rate_margin'];

    protected static string $actualExchangeRateCachePrefix = 'actual-exchange-rate';

    protected static function bootConvertsCurrency()
    {
        static::updated(function (Model $model) {
            if ($model->wasChanged(['target_currency_id', 'source_currency_id'])) {
                $model->cacheActualExchangeRate();

                if (method_exists($model, 'forgetCachedMappingReview')) {
                    $model->forgetCachedMappingReview();
                }
            }
        });
    }

    protected function initializeConvertsCurrency()
    {
        $this->fillable = array_merge($this->fillable, static::$currenciesAttributes);
    }

    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function getActualExchangeRate(): float
    {
        return app('exchange.service')->getTargetRate($this->sourceCurrency, $this->targetCurrency);
    }

    public function getBaseExchangeRateAttribute(): float
    {
        return app('exchange.service')->getBaseRate($this->sourceCurrency);
    }

    public function getTargetExchangeRateAttribute(): float
    {
        if (is_null($this->target_currency_id)) {
            return $this->actual_exchange_rate;
        }

        return $this->actual_exchange_rate + ($this->actual_exchange_rate * $this->exchange_rate_margin / 100);
    }

    public function getActualExchangeRateAttribute(): float
    {
        return cache()->sear($this->getActualExchangeRateCacheKey(), fn () => $this->getActualExchangeRate());
    }

    public function getExchangeRateMarginAttribute($value): float
    {
        return $value ?? setting('default_exchange_rate_margin') ?? ER_MARGIN_DEFAULT;
    }

    public function enableExchangeRateConversion(): self
    {
        $this->exchangeRateConversion = true;

        return $this;
    }

    public function disableExchangeRateConversion(): self
    {
        $this->exchangeRateConversion = false;

        return $this;
    }

    public function convertExchangeRate(float $value): float
    {
        if ($this->exchangeRateConversion) {
            return $value * $this->targetExchangeRate;
        }

        return $value;
    }

    protected function cacheActualExchangeRate(): void
    {
        cache()->forever($this->getActualExchangeRateCacheKey(), $this->getActualExchangeRate());
    }

    protected function getActualExchangeRateCacheKey(): string
    {
        return static::$actualExchangeRateCachePrefix.':'.$this->getKey();
    }
}
