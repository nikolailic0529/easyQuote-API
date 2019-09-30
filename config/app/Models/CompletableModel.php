<?php namespace App\Models;

use App\Models\UuidModel;

abstract class CompletableModel extends UuidModel
{
    public function getLastDraftedStepAttribute()
    {
        $dictionary = $this->getCompletenessDictionary();
        $stage = collect($dictionary)->search($this->getAttribute('completeness'), true);

        return $stage;
    }

    public function setLastDraftedStepAttribute(string $value)
    {
        $dictionary = $this->getCompletenessDictionary();
        $completeness = collect($dictionary)->get($value) ?? $this->attributes['completeness'];

        $this->setAttribute('completeness', $completeness);
    }

    /**
     * Get Dictionary for Completed Stages
     *
     * @return array
     */
    abstract public function getCompletenessDictionary();
}
