<?php namespace App\Observers;

use App\Models\QuoteTemplate\TemplateField;

class TemplateFieldObserver
{
    /**
     * Handle the TemplateField "saving" event.
     *
     * @param TemplateField $templateField
     * @return void
     */
    public function saving(TemplateField $templateField)
    {
        if($this->exists($templateField)) {
            throw new \ErrorException(__('template_field.exists_exception'));
        }
    }

    /**
     * Handle the TemplateField "updating" event.
     *
     * @param TemplateField $templateField
     * @return void
     */
    public function updating(TemplateField $templateField)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($templateField->isSystem()) {
            throw new \ErrorException(__('template_field.system_updating_exception'));
        }
    }

    /**
     * Handle the TemplateField "deleting" event.
     *
     * @param TemplateField $templateField
     * @return void
     */
    public function deleting(TemplateField $templateField)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($templateField->isSystem()) {
            throw new \ErrorException(__('template_field.system_deleting_exception'));
        }

        if($templateField->isAttached()) {
            throw new \ErrorException(__('template_field.attached_deleting_exception'));
        }
    }

    private function exists(TemplateField $templateField)
    {
        return $templateField
            ->where('id', '!=', $templateField->id)
            ->where(function ($query) {
                $query->where('is_system', true)
                    ->orWhere('user_id', request()->user()->id);
            })
            ->where(function ($query) use ($templateField) {
                $query->where('name', $templateField->name)
                    ->orWhere('header', $templateField->header);
            })
            ->exists();
    }
}
