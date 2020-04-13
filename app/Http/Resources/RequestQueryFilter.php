<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Str;

class RequestQueryFilter
{
    public function attach($resource, Request $request = null)
    {
        $request ??= request();
        $availableIncludes = optional($resource)->availableIncludes ?? [];

        return tap(
            $resource,
            fn ($resource) => $this->getRequestIncludes($request, $availableIncludes)
                ->each(fn ($include) => $resource->load($include))
        );
    }
    protected function getRequestIncludes(Request $request, array $availableIncludes = [])
    {
        return collect(data_get($request->input(), 'include', []))
            ->transform(fn ($include) => Str::camel($include))
            ->intersect($availableIncludes);
    }
}
