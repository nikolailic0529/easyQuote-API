<?php

namespace App\Traits\Quote;

trait HasAdditionalHtmlAttributes
{
    protected static function bootHasAdditionalHtmlAttributes()
    {
        if (property_exists(static::class, 'logAttributes')) {
            static::$logAttributes = array_merge(static::$logAttributes, ['additional_notes:additional_notes_text', 'additional_details:additional_details_text']);
        }
    }

    public function initializeHasAdditionalHtmlAttributes()
    {
        $this->fillable = array_merge($this->fillable, ['additional_notes', 'additional_details']);
    }

    public function getAdditionalNotesTextAttribute()
    {
        return $this->stripHtml($this->additional_notes);
    }

    public function getAdditionalDetailsTextAttribute()
    {
        return $this->stripHtml($this->additional_details);
    }

    protected function stripHtml(?string $value)
    {
        if (!isset($value)) {
            return $value;
        }

        $value = preg_replace('/<p(.*?)>((.*?)+)\<\/p>/', '${2}<br/>', strip_tags($value, '<br><p>'));

        /**
         * Prevent repeated line-breaks.
         */
        $value = preg_replace('/(<br.*?>)+/', '<br/>', $value);
        $value = trim(preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $value));

        return $value;
    }
}
