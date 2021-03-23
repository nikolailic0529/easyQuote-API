<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $availableIncludes = optional($resource)->availableIncludes ?? [];

        $requestIncludes = $this->getRequestIncludes($availableIncludes)->all();

        $resource->load($requestIncludes);

        return $resource;
    }
    protected function getRequestIncludes(array $availableIncludes = []): Collection
    {
        return collect(data_get($this->request->input(), 'include', []))
            ->transform(fn ($include) => Str::camel($include))
            ->intersect($availableIncludes)
            ->values();
    }
}
