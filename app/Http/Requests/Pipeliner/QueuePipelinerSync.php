<?php

namespace App\Http\Requests\Pipeliner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueuePipelinerSync extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'strategies' => ['bail', 'nullable', 'array'],
            'strategies.*' => ['bail', 'required', 'string', Rule::in($this->getAllowedStrategyNames())],
        ];
    }

    public function getAllowedStrategyNames(): array
    {
        return array_keys(config('pipeliner.sync.strategies'));
    }

    public function getStrategies(): array
    {
        if ($this->missing('strategies')) {
            return [];
        }

        return $this->collect('strategies')
            ->map(static function (string $name): string {
                return config('pipeliner.sync.strategies')[$name];
            })
            ->all();
    }
}
