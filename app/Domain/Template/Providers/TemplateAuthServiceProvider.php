<?php

namespace App\Domain\Template\Providers;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Policies\ContractTemplatePolicy;
use App\Domain\Template\Policies\HpeContractTemplatePolicy;
use App\Domain\Template\Policies\OpportunityFormPolicy;
use App\Domain\Template\Policies\QuoteTaskTemplatePolicy;
use App\Domain\Template\Policies\QuoteTemplatePolicy;
use App\Domain\Template\Policies\SalesOrderTemplatePolicy;
use App\Domain\Worldwide\Models\OpportunityForm;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TemplateAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(QuoteTemplate::class, QuoteTemplatePolicy::class);
        Gate::policy(ContractTemplate::class, ContractTemplatePolicy::class);
        Gate::policy(HpeContractTemplate::class, HpeContractTemplatePolicy::class);
        Gate::policy(SalesOrderTemplate::class, SalesOrderTemplatePolicy::class);
        Gate::policy(OpportunityForm::class, OpportunityFormPolicy::class);

        Gate::define('view_quote_task_template', [QuoteTaskTemplatePolicy::class, 'view']);
        Gate::define('update_quote_task_template', [QuoteTaskTemplatePolicy::class, 'update']);
    }
}
