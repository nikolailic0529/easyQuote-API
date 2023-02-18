<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeQuoteOwnershipRequest extends FormRequest
{
    public function rules(): array
    {
        $model = new WorldwideQuoteVersion();

        return [
            'version_id' => [
                'required_if:version_ownership,true', 'nullable', 'uuid',
                Rule::exists($model::class, $model->getKeyName())
                    ->where($model->worldwideQuote()->getForeignKeyName(), $this->getQuote()->getKey())
                    ->withoutTrashed(),
            ],
            'version_ownership' => ['bool'],
        ];
    }

    public function attributes(): array
    {
        return [
            'version_id' => 'Version',
            'version_ownership' => 'Version Ownership Transfer',
        ];
    }

    /**
     * @throws ModelNotFoundException
     */
    public function mustGetQuoteVersion(): WorldwideQuoteVersion
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return WorldwideQuoteVersion::query()
            ->whereBelongsTo($this->getQuote())
            ->findOrFail($this->input('version_id'));
    }

    private function getQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }
}
