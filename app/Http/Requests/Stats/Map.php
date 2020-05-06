<?php

namespace App\Http\Requests\Stats;

use Illuminate\Foundation\Http\FormRequest;

class Map extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',

            'ne_lat' => 'required|numeric',
            'ne_lng' => 'required|numeric',
            
            'sw_lat' => 'required|numeric',
            'sw_lng' => 'required|numeric'
        ];
    }
}
