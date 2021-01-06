<?php

namespace App\Observers;

use App\Models\Template\QuoteTemplate;
use App\Models\Template\ContractTemplate;
use App\Models\Template\HpeContractTemplate;

class QuoteTemplateObserver
{
    /**
     * Handle the Template "relicating" event.
     *
     * @param QuoteTemplate|ContractTemplate|HpeContractTemplate $quoteTemplate
     * @return void
     */
    public function replicating($template)
    {
        $template->name = "{$template->name} [copy]";
    }
}
