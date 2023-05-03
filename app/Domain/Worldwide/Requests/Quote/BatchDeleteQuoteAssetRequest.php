<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchDeleteQuoteAssetRequest extends FormRequest
{
    private readonly ?Collection $assetModels;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assets' => ['required', 'array'],
            'assets.*.id' => ['required', 'uuid', 'distinct', Rule::exists(WorldwideQuoteAsset::class, 'id')],
        ];
    }

    /**
     * @return Collection|\App\Domain\Worldwide\Models\WorldwideQuoteAsset[]
     */
    public function getAssetModels(): Collection
    {
        return $this->assetModels ??= WorldwideQuoteAsset::query()->find($this->input('assets.*.id'));
    }
}
