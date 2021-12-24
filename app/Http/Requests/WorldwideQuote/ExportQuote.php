<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\Export\WorldwideQuotePreviewData;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Http\FormRequest;

class ExportQuote extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
