<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait Handleable
{
    protected $shouldBeHandled = false;

    public function markAsHandled(): bool
    {
        return $this->forceFill([
            'handled_at' => Carbon::now()->toDateTimeString(),
        ])->save();
    }

    public function markAsUnHandled(): bool
    {
        return $this->forceFill([
            'handled_at' => null,
        ])->save();
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

    public function getRowsCountAttribute()
    {
        return (int) cache()->sear("rows-count:{$this->id}", function () {
            return $this->rowsData()->count();
        });
    }

    public function setRowsCount(int $count)
    {
        return cache()->forever("rows-count:{$this->id}", $count);
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
}
