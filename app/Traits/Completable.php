<?php

namespace App\Traits;

trait Completable
{

    /**
     * Get Dictionary for Completed Stages
     *
     * @return array
     */
    abstract public function getCompletenessDictionary();
    
    public function transformDraftedStep($completeness)
    {
        $dictionary = $this->getCompletenessDictionary();
        $stage = collect($dictionary)->search($completeness, true);

        return $stage;
    }

    public function getLastDraftedStepAttribute()
    {
        return $this->transformDraftedStep($this->completeness);
    }

    public function setLastDraftedStepAttribute(string $value): void
    {
        $dictionary = $this->getCompletenessDictionary();
        $completeness = collect($dictionary)->get($value) ?? $this->completeness;

        $this->setAttribute('completeness', $completeness);
    }

    public static function modelCompleteness()
    {
        return (new static)->getCompletenessDictionary();
    }
}
