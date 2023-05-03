<?php

namespace App\Domain\Template\Observers;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Rescue\Models\ContractTemplate;

class QuoteTemplateObserver
{
    /**
     * Handle the Template "relicating" event.
     *
     * @param \App\Domain\Rescue\Models\QuoteTemplate|ContractTemplate|HpeContractTemplate $quoteTemplate
     *
     * @return void
     */
    public function replicating($template)
    {
        $template->name = "{$template->name} [copy]";
    }
}
