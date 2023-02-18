<?php

namespace App\Foundation\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RequestQueryFilter
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function attach($resource)
    {
        $availableIncludes = (array) ($resource->availableIncludes ?? []);
        $translatedIncludes = (array) ($resource->translatedIncludes ?? []);

        $requestIncludes = $this->resolveRequestIncludes($availableIncludes, $translatedIncludes);

        $resource->load($requestIncludes);

        return $resource;
    }

    protected function resolveRequestIncludes(array $availableIncludes = [], array $translatedIncludes = []): array
    {
        if (empty($availableIncludes)) {
            return [];
        }

        $requestIncludes = array_filter(Arr::wrap($this->request->input('include')), 'is_string');

        $requestIncludes = array_map([Str::class, 'camel'], $requestIncludes);

        $filteredIncludes = array_values(array_intersect($requestIncludes, $availableIncludes));

        if (empty($translatedIncludes)) {
            return $filteredIncludes;
        }

        return array_map(function (string $include) use ($translatedIncludes) {
            return $translatedIncludes[$include] ?? $include;
        }, $filteredIncludes);
    }
}
