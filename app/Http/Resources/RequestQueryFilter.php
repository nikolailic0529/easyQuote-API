<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RequestQueryFilter
{
    public function attach($resource, Request $request = null)
    {
        $request ??= request();
        $availableIncludes = optional($resource)->availableIncludes ?? [];

        $requestIncludes = $this->getRequestIncludes($request, $availableIncludes)->all();

        $resource->load($requestIncludes);

        return $resource;
    }
    protected function getRequestIncludes(Request $request, array $availableIncludes = []): Collection
    {
        return collect(data_get($request->input(), 'include', []))
            ->transform(fn ($include) => Str::camel($include))
            ->intersect($availableIncludes)
            ->values();
    }
}
