<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunityValidationResult;
use Illuminate\Database\ConnectionResolverInterface;

class ValidateOpportunityService
{
    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected OpportunityEntityValidator $validator)
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

                $this->connectionResolver->connection()->transaction(static fn () => $result->save());
            });
    }
}
