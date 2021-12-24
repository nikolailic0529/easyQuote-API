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
            'selected_groups' => [
              'bail', 'present', 'array'
            ],
            'selected_groups.*' => [
                'bail', 'uuid'
            ],
            'reject' => [
                'bail', 'required', 'boolean'
            ],
            'sort_rows_column' => [
                'bail', 'nullable', Rule::in(['sku', 'product_name', 'serial_no', 'expiry_date', 'price', 'service_level_description', 'vendor_short_code', 'machine_address', 'buy_currency_code'])
            ],
            'sort_rows_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc'])
            ],
            'sort_assets_groups_column' => [
                'bail', 'nullable', Rule::in(['group_name', 'search_text', 'assets_count', 'assets_sum'])
            ],
            'sort_assets_groups_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc'])
            ],
            'use_groups' => [
                'bail', 'required', 'boolean',
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
            'selected_groups' => $this->input('selected_groups'),
            'reject' => $this->boolean('reject'),
            'sort_assets_groups_column' => $this->input('sort_assets_groups_column'),
            'sort_assets_groups_direction' => $this->input('sort_assets_groups_direction'),
            'sort_rows_column' => $this->input('sort_rows_column'),
            'sort_rows_direction' => $this->input('sort_rows_direction'),
            'use_groups' => $this->boolean('use_groups'),
            'stage' => PackQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }
}
