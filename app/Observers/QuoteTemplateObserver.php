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

    /**
     * Handle the Template "deleting" event.
     *
     * @param App\Models\QuoteTemplate\BaseQuoteTemplate $quoteTemplate
     * @return void
     */
    public function deleting(BaseQuoteTemplate $template)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($template->isAttached(), QTAD_01, 'QTAD_01', 409);
    }
}
