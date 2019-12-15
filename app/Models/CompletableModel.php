<?php

namespace App\Models;

use App\Models\UuidModel;

abstract class CompletableModel extends UuidModel
{
    public static function transformDraftedStep($completeness)
    {
        $dictionary = static::getCompletenessDictionary();
        $stage = collect($dictionary)->search($completeness, true);

        return $stage;
    }

    public function getLastDraftedStepAttribute()
    {
        static::transformDraftedStep($this->completeness);
    }

    public function setLastDraftedStepAttribute(string $value)
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
