<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\DataTransferObjects\CustomDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutableCustomDiscountData;
use Illuminate\Foundation\Http\FormRequest;

class ShowMarginAfterCustomDiscountRequest extends FormRequest
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
                'bail', 'required', 'min:0', 'max:999999',
            ],
        ];
    }

    public function getCustomDiscountData(): ?ImmutableCustomDiscountData
    {
        return $this->customDiscountData ??= new ImmutableCustomDiscountData(
            new CustomDiscountData([
                'value' => (float) $this->input('custom_discount'),
            ])
        );
    }
}
