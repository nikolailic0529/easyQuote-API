<?php namespace App\Observers;

use App\Models\QuoteTemplate\QuoteTemplate;

class QuoteTemplateObserver
{
    /**
     * Handle the QuoteTemplate "saving" event.
     *
     * @param QuoteTemplate $quoteTemplate
     * @return void
     */
    public function saving(QuoteTemplate $quoteTemplate)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($this->exists($quoteTemplate)) {
            throw new \ErrorException(__('template.exists_exception'));
        }
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
        //
    }

    private function exists(QuoteTemplate $quoteTemplate)
    {
        return $quoteTemplate
            ->where('id', '!=', $quoteTemplate->id)
            ->whereName($quoteTemplate->name)
            ->exists();
    }
}