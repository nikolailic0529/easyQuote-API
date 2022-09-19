<?php

namespace App\Jobs\Opportunity;

use App\Models\Company;
use App\Models\Opportunity;
use App\Services\Opportunity\ValidateOpportunityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateOpportunitiesOfCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company->withoutRelations();
    }

    public function handle(ValidateOpportunityService $validator): void
    {
        Opportunity::query()
            ->whereBelongsTo($this->company, relationshipName: 'primaryAccount')
            ->orWhereBelongsTo($this->company, relationshipName: 'endUser')
            ->lazyById(100)
            ->each(static function (Opportunity $opportunity) use ($validator): void {
                $validator->performValidation($opportunity);
            });
    }
}
