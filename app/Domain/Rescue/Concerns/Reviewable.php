<?php

namespace App\Domain\Rescue\Concerns;

trait Reviewable
{
    /**
     * Determine how to display the Quote data.
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
