<?php

/**
 * Filter Query String Parameters for the given resource.
 */
if (!function_exists('filter')) {
    function filter($resource)
    {
        return app('request.filter')->attach($resource);
    }
}
