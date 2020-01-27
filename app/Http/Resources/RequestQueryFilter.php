<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Str;

class RequestQueryFilter
{
    public function attach($resource, Request $request = null)
    {
        $request = $request ?? request();
        $availableIncludes = optional($resource)->availableIncludes ?? [];

        return tap($resource, function ($resource) use ($request, $availableIncludes) {
            $this->getRequestIncludes($request, $availableIncludes)
                ->each(function ($include) use ($resource) {
                    $resource->load($include);
                });
        });
    }
    protected function getRequestIncludes(Request $request, array $availableIncludes = [])
    {
        return collect(data_get($request->input(), 'include', []))
            ->transform(function ($include) {
                return Str::camel($include);
            })
            ->intersect($availableIncludes);
    }
}
