<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Discounts\CustomDiscountData;
use App\DTO\Discounts\ImmutableCustomDiscountData;
use Illuminate\Foundation\Http\FormRequest;

class ShowMarginAfterCustomDiscount extends FormRequest
{
    protected ?ImmutableCustomDiscountData $customDiscountData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'custom_discount' => [
                'bail', 'required', 'min:0', 'max:999999'
            ]
        ];
    }

    /**
     * @return ImmutableCustomDiscountData|null
     */
    public function getCustomDiscountData(): ?ImmutableCustomDiscountData
    {
        return $this->customDiscountData ??= new ImmutableCustomDiscountData(
            new CustomDiscountData([
                'value' => (float)$this->input('custom_discount')
            ])
        );
    }
}
