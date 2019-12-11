<?php

namespace App\Observers;

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
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($this->exists($templateField), 409, __('template_field.exists_exception'));
    }

    /**
     * Handle the TemplateField "updating" event.
     *
     * @param TemplateField $templateField
     * @return void
     */
    public function updating(TemplateField $templateField)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($templateField->isSystem(), 409, __('template_field.system_updating_exception'));
    }

    /**
     * Handle the TemplateField "deleting" event.
     *
     * @param TemplateField $templateField
     * @return void
     */
    public function deleting(TemplateField $templateField)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($templateField->isSystem(), 409, __('template_field.system_updating_exception'));
        abort_if($templateField->isAttached(), 409, __('template_field.attached_deleting_exception'));
    }

    private function exists(TemplateField $templateField)
    {
        return $templateField
            ->query()
            ->where('id', '!=', $templateField->id)
            ->where(function ($query) use ($templateField) {
                $query->where('name', $templateField->name)
                    ->orWhere('header', $templateField->header);
            })
            ->exists();
    }
}
