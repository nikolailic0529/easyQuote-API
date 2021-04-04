<?php

namespace App\Http\Requests\UnifiedQuote;

use App\DTO\UnifiedQuote\UnifiedQuotesRequestData;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UnifiedQuotesRequest extends FormRequest
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

    public function getUnifiedQuotesRequestData(): UnifiedQuotesRequestData
    {
        /** @var User $user */
        $user = $this->user();

        $gate = $this->container[Gate::class];

        return new UnifiedQuotesRequestData([
            'user_id' => $user->getKey(),
            'show_rescue_entities' => $gate->allows('viewAny', Quote::class),
            'show_worldwide_entities' => $gate->allows('viewAny', WorldwideQuote::class),
            'show_own_entities_only' => false === $gate->allows('viewQuotesOfAnyUser')
        ]);
    }
}
