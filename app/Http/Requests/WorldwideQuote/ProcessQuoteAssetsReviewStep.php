<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\PackAssetsReviewStage;
use App\Enum\PackQuoteStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessQuoteAssetsReviewStep extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'selected_rows' => [
                'bail', 'present', 'array',
            ],
            'selected_rows.*' => [
                'bail', 'uuid'
            ],
            'reject' => [
                'bail', 'required', 'boolean'
            ],
            'sort_rows_column' => [
                'bail', 'nullable', Rule::in(['sku', 'product_name', 'serial_no', 'expiry_date', 'price', 'service_level_description', 'vendor_short_code', 'machine_address'])
            ],
            'sort_rows_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc'])
            ],
            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels())
            ],
        ];
    }

    public function getStage(): PackAssetsReviewStage
    {
        return new PackAssetsReviewStage([
            'selected_rows' => $this->input('selected_rows'),
            'reject' => $this->boolean('reject'),
            'sort_rows_column' => $this->input('sort_rows_column'),
            'sort_rows_direction' => $this->input('sort_rows_direction'),
            'stage' => PackQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }
}
