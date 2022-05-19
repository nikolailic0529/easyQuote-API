<?php

namespace App\Http\Requests\WorldwideQuote;

use App\Models\WorldwideQuoteAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchDeleteQuoteAsset extends FormRequest
{
    private readonly ?Collection $assetModels;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'assets' => ['required', 'array'],
            'assets.*.id' => ['required', 'uuid', 'distinct', Rule::exists(WorldwideQuoteAsset::class, 'id')],
        ];
    }

    /**
     * @return Collection|WorldwideQuoteAsset[]
     */
    public function getAssetModels(): Collection
    {
        return $this->assetModels ??= WorldwideQuoteAsset::query()->find($this->input('assets.*.id'));
    }
}
