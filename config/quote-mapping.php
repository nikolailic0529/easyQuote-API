<?php

return [
    'rescue_quote' => [
        'fields' => [
            'product_no',
            'description',
            'serial_no',
            'date_from',
            'date_to',
            'qty',
            'price',
            'searchable',
            'service_level_description',
            'system_handle',
            'pricing_document',
        ],
    ],

    'worldwide_quote' => [
        'fields' => [
            'product_no',
            'service_sku',
            'description',
            'serial_no',
            'date_from',
            'date_to',
            'qty',
            'price',
            'searchable',
            'service_level_description',
        ],

        'required_fields' => [
            'product_no',
            'service_sku',
            'serial_no',
            'date_from',
            'date_to',
            'price',
            'service_level_description',
        ],
    ],
];
