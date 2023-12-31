<?php

namespace App\Domain\UnifiedContract\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class EntityLookupRequest extends FormRequest
{
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

    public function collapseElasticsearchResult(array $result): array
    {
        $hits = array_map(function (array $hit) {
            return head($hit['_source']);
        }, Arr::get($result, 'hits.hits') ?? []);

        return [
            'data' => $hits,
        ];
    }
}
