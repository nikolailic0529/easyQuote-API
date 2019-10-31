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
        $name = "{$quoteTemplate->name} [copy]";
        $user_id = request()->user()->id;
        $collaboration_id = request()->user()->collaboration_id;
        $is_system = false;

        $quoteTemplate->forceFill(compact('name', 'user_id', 'collaboration_id', 'is_system'));
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
