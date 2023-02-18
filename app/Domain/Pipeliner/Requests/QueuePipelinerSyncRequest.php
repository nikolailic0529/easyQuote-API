<?php

namespace App\Domain\Pipeliner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueuePipelinerSyncRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'strategies' => ['bail', 'nullable', 'array'],
            'strategies.*' => ['bail', 'required', 'string', Rule::in(config('pipeliner.sync.aggregate_strategies'))],
        ];
    }

    public function getStrategies(): array
    {
        if ($this->missing('strategies')) {
            return [];
        }

        $map = config('pipeliner.sync.strategies');

        return $this->collect('strategies')
            ->sortBy(static function (string $name): int|float {
                return array_search($name, config('pipeliner.sync.aggregate_strategies'), true) ?: INF;
            })
            ->map(static function (string $name) use ($map): string {
                return $map[$name];
            })
            ->all();
    }
}
