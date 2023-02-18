<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Rescue\Contracts\QuoteDraftedRepositoryInterface as DraftedQuotes;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CopyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(DraftedQuotes $draftedQuotes)
    {
        $quote = $this->route('submitted');

        return !$draftedQuotes->rfqExist(optional($quote->customer)->rfq);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     */
    protected function failedAuthorization()
    {
        throw new UnprocessableEntityHttpException('Drafted Activated Quote with the same RFQ already exists.');
    }
}
