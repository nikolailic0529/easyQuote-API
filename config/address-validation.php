<?php

return [
    /*
     * The api key used when sending Geocoding requests to Google.
     */
    'key' => env('GOOGLE_ADDRESS_VALIDATION_API_KEY', ''),

    'log_formats' => [
        'request' => "REQUEST: {method} - {uri} - HTTP/{version} - {req_headers} - {req_body}",
        'response' => "RESPONSE: {code} - {res_body}",
    ],
];