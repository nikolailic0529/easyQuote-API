<?php

namespace App\Traits;

trait Reviewable
{
    /**
     * Determine how to display the Quote data.
     *
     * @var boolean
     */
    protected bool $isReview = false;

    public function getIsReviewAttribute(): bool
    {
        return $this->isReview;
    }

    public function enableReview(): self
    {
        $this->isReview = true;
        return $this;
    }

    public function disableReview(): self
    {
        $this->isReview = false;
        return $this;
    }
}
