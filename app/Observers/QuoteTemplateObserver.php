<?php

namespace App\Observers;

use App\Models\QuoteTemplate\QuoteTemplate;

class QuoteTemplateObserver
{

    /**
     * Handle the QuoteTemplate "relicating" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function replicating(QuoteTemplate $quoteTemplate)
    {
        $quoteTemplate->name = "{$quoteTemplate->name} [copy]";
    }

    /**
     * Handle the QuoteTemplate "deleting" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function deleting(QuoteTemplate $quoteTemplate)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($quoteTemplate->isAttached(), 409, __('template.attached_deleting_exception'));
    }
}
