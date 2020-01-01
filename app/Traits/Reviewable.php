<?php

namespace App\Traits;

trait Reviewable
{
    /**
     * Determine how to display the Quote data.
     *
     * @var boolean
     */
    protected $isReview = false;

    public function getIsReviewAttribute(): bool
    {
        return $this->isReview;
    }

    public function enableReview()
    {
        $this->isReview = true;
        return $this;
    }

    public function disableReview()
    {
        $this->isReview = false;
        return $this;
    }
}
