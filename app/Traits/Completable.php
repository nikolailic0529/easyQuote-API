<?php

namespace App\Traits;

trait Completable
{
    public static function transformDraftedStep($completeness)
    {
        $dictionary = static::getCompletenessDictionary();
        $stage = collect($dictionary)->search($completeness, true);

        return $stage;
    }

    public function getLastDraftedStepAttribute()
    {
        return static::transformDraftedStep($this->completeness);
    }

    public function setLastDraftedStepAttribute(string $value): void
    {
        $dictionary = $this->getCompletenessDictionary();
        $completeness = collect($dictionary)->get($value) ?? $this->completeness;

        $this->setAttribute('completeness', $completeness);
    }

    /**
     * Get Dictionary for Completed Stages
     *
     * @return array
     */
    abstract public static function getCompletenessDictionary();
}
