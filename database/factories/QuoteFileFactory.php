<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\QuoteFileFormat;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(QuoteFile::class, function (Faker $faker) {
    return [
        'original_file_path' => Str::random(40).'.pdf',
        'pages' => 7,
        'file_type' => 'Distributor Price List',
        'quote_file_format_id' => QuoteFileFormat::where('extension', 'pdf')->value('id'),
        'imported_page' => 2,
    ];
});

$factory->state(QuoteFile::class, 'rescue-price-list', [
    'file_type' => 'Distributor Price List',
]);

$factory->state(QuoteFile::class, 'rescue-payment-schedule', [
    'file_type' => 'Payment Schedule',
]);
