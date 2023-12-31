<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitDistributionRequest extends FormRequest
{
    protected ?WorldwideQuote $wwQuote = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_quote_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideQuote::class, 'id')->whereNull('deleted_at'),
                Rule::unique(WorldwideQuote::class, 'id')->whereNotNull('submitted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'worldwide_quote_id.unique' => 'Unable to initialize a new Quote Distribution on submitted Quote.',
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->wwQuote ??= WorldwideQuote::query()->findOrFail($this->input('worldwide_quote_id'));
    }
}
