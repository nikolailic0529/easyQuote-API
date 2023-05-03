<?php

return [
    /*
     * The maximum number of results that will be returned
     * when using the JSON API paginator.
     */
    'max_results' => 500,

    /*
     * The default number of results that will be returned
     * when using the JSON API paginator.
     */
    'default_size' => 20,

    /*
     * The key of the page[x] query string parameter for page number.
     */
    'number_parameter' => 'page',

    /*
     * The key of the page[x] query string parameter for page size.
     */
    'size_parameter' => 'per_page',

    /*
     * The name of the macro that is added to the Eloquent query builder.
     */
    'method_name' => 'jsonPaginate',

    /*
     * Count rows cache ttl in minutes.
     */
    'count_cache_ttl' => 60,
];
