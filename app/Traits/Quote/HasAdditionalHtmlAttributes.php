<?php

namespace App\Traits\Quote;

trait HasAdditionalHtmlAttributes
{
    public function initializeHasAdditionalHtmlAttributes()
    {
        $this->fillable = array_merge($this->fillable, ['additional_notes', 'additional_details']);

        if (property_exists($this, 'logAttributes')) {
            static::$logAttributes = array_merge(static::$logAttributes, ['additional_notes:additional_notes_text', 'additional_details:additional_details_text']);
        }
    }

    public function getAdditionalNotesTextAttribute()
    {
        return $this->stripHtml($this->attributes['additional_notes'] ?? null);
    }

    public function getAdditionalDetailsTextAttribute()
    {
        return $this->stripHtml($this->attributes['additional_details'] ?? null);
    }

    protected function stripHtml(?string $value)
    {
        if (!isset($value)) {
            return $value;
        }

        $value = preg_replace(['/<p(.*?)>((.*?)+)\<\/p>/', '/(<br.*>)+/'], ['${2}' . PHP_EOL, PHP_EOL], strip_tags($value, '<br><p>'));
        $value = trim(preg_replace('/(\n|\r\n)+/', PHP_EOL, $value));

        return $value;
    }
}
