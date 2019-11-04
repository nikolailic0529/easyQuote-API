<?php namespace App\Observers;

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
     * Handle the QuoteTemplate "saving" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function saving(QuoteTemplate $quoteTemplate)
    {
        //
    }

    /**
     * Handle the QuoteTemplate "updating" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function updating(QuoteTemplate $quoteTemplate)
    {
        //
    }

    /**
     * Handle the QuoteTemplate "deleting" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function deleting(QuoteTemplate $quoteTemplate)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($quoteTemplate->isAttached()) {
            throw new \ErrorException(__('template.attached_deleting_exception'));
        }
    }

    private function exists(QuoteTemplate $quoteTemplate)
    {
        return $quoteTemplate
            ->where('id', '!=', $quoteTemplate->id)
            ->whereName($quoteTemplate->name)
            ->exists();
    }
}
