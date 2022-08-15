<?php

namespace App\Services\Opportunity;

use App\Models\Opportunity;
use App\Models\OpportunityValidationResult;
use Illuminate\Database\ConnectionResolverInterface;

class ValidateOpportunityService
{
    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected OpportunityEntityValidator  $validator)
    {
    }

    public function work(callable $progressCallback = null): void
    {
        $progressCallback ??= static function (mixed $result) {};

        foreach (Opportunity::query()->lazyById(100) as $opportunity) {
            $result = $this->performValidation($opportunity);

            $progressCallback($result);
        }
    }

    public function performValidation(Opportunity $opportunity): OpportunityValidationResult
    {
        return tap(OpportunityValidationResult::query()->whereBelongsTo($opportunity)->firstOrNew(),
            function (OpportunityValidationResult $result) use ($opportunity) {
                $messages = ($this->validator)($opportunity);

                $passes = $messages->isEmpty();

                $result->opportunity()->associate($opportunity);
                $result->messages = $messages;
                $result->is_passed = $passes;

                $this->connectionResolver->connection()->transaction(static fn() => $result->save());
            });
    }
}