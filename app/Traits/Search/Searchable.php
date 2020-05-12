<?php

namespace App\Traits\Search;

use App\Observers\SearchObserver;

trait Searchable
{
    protected bool $reindexEnabled = true;

    public static function bootSearchable()
    {
        if (!app()->runningUnitTests() && config('services.search.enabled')) {
            static::observe(SearchObserver::class);
        }
    }

    public function disableReindex(): self
    {
        $this->reindexEnabled = false;

        return $this;
    }

    public function enableReindex(): self
    {
        $this->reindexEnabled = true;

        return $this;
    }

    public function reindexEnabled(): bool
    {
        return $this->reindexEnabled === true;
    }

    public function reindexDisabled(): bool
    {
        return $this->reindexEnabled === false;
    }

    public function getSearchIndex()
    {
        return $this->getTable();
    }

    public function getSearchType()
    {
        if (property_exists($this, 'useSearchType')) {
            return $this->useSearchType;
        }

        return $this->getTable();
    }

    public function toSearchArray()
    {
        return $this->toArray();
    }
}
