<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\{
    QuoteFile\ImportableColumn,
    QuoteFile\ImportedRow,
    Template\TemplateField,
};
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(ImportedRow::class, function (Faker $faker) {
    $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
    $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

    return [
        'id'            => (string) Str::uuid(4),
        'columns_data'  => collect([
            $templateFields->get('product_no') => ['value' => $faker->regexify('/\d{6}-[A-Z]\d{2}/'), 'header' => 'Product Number', 'importable_column_id' => $importableColumns->get('product_no')],
            $templateFields->get('serial_no') => ['value' => $faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'), 'header' => 'Serial Number', 'importable_column_id' => $importableColumns->get('serial_no')],
            $templateFields->get('description') => ['value' => $faker->text, 'header' => 'Description', 'importable_column_id' => $importableColumns->get('description')],
            $templateFields->get('date_from') => ['value' => now()->format('d/m/Y'), 'header' => 'Coverage from', 'importable_column_id' => $importableColumns->get('date_from')],
            $templateFields->get('date_to') => ['value' => now()->addYears(2)->format('d/m/Y'), 'header' => 'Coverage to', 'importable_column_id' => $importableColumns->get('date_to')],
            $templateFields->get('qty') => ['value' => 1, 'header' => 'Quantity', 'importable_column_id' => $importableColumns->get('qty')],
            $templateFields->get('price') => ['value' => "1,192.00", 'header' => 'Price', 'importable_column_id' => $importableColumns->get('price')],
        ]),
        'page'          => mt_rand(2, 4)
    ];
});
