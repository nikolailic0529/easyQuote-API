<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;

class QuoteReviewResource extends QuoteResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return Arr::get(parent::toArray($request), 'quote_data');
    }
}
