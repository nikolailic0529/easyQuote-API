<?php

namespace App\Observers;

use App\Models\QuoteTemplate\BaseQuoteTemplate;

class QuoteTemplateObserver
{
    /**
     * Handle the Template "relicating" event.
     *
     * @param \App\Models\QuoteTemplate\BaseQuoteTemplate $quoteTemplate
     * @return void
     */
    public function replicating(BaseQuoteTemplate $template)
    {
        $template->name = "{$template->name} [copy]";
    }
}
