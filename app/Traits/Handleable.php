<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Handleable
{
    protected $shouldBeHandled = false;

    public function markAsHandled(): bool
    {
        return $this->forceFill(['handled_at' => now()])->save();
    }

    public function markAsUnHandled(): bool
    {
        return $this->forceFill(['handled_at' => null])->save();
    }

    public function isHandled(): bool
    {
        return !is_null($this->handled_at);
    }

    public function scopeHandled(Builder $query): Builder
    {
        return $query->whereNotNull('handled_at');
    }

    public function scopeUnHandled(Builder $query): Builder
    {
        return $query->whereNull('handled_at');
    }

    public function shouldHandle(): self
    {
        $this->shouldBeHandled = true;

        return $this;
    }

    public function shouldNotHandle(): self
    {
        $this->shouldBeHandled = false;

        return $this;
    }

    public function getShouldBeHandledAttribute(): bool
    {
        return $this->shouldBeHandled;
    }

    public function getShouldNotBeHandledAttribute(): bool
    {
        return !$this->shouldBeHandled;
    }

    public function getRowsCountAttribute(): int
    {
        return (int) cache()->sear($this->getRowsCountCacheKey(), function () {
            return $this->rowsData()->count();
        });
    }

    public function setRowsCount(int $count): void
    {
        cache()->forever($this->getRowsCountCacheKey(), $count);
    }

    public function getRowsProcessedCountAttribute()
    {
        return $this->rowsData()->processed()->count();
    }

    public function getProcessingStatusAttribute()
    {
        if ($this->isSchedule()) {
            return 'completed';
        }

        $percentage = $this->getAttribute('processing_percentage');

        return $percentage >= 100 ? 'completed' : 'processing';
    }

    public function getProcessingStateAttribute()
    {
        return [
            'status' => $this->processing_status,
            'processed' => $this->processing_percentage
        ];
    }

    public function getProcessingPercentageAttribute()
    {
        if ($this->isSchedule()) {
            return 100;
        }

        $rowsCount = $this->getAttribute('rows_count') ?: 1;
        $processedRowsCount = $this->getAttribute('rows_processed_count');

        if ($processedRowsCount > $rowsCount) {
            $rowsCount = $processedRowsCount;
        }

        return floor($processedRowsCount / $rowsCount * 100);
    }

    protected function getRowsCountCacheKey(): string
    {
        return 'rows-count:' . $this->id;
    }
}
