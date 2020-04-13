<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Attachment;
use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

$factory->define(Attachment::class, function (Faker $faker) {
    $ext = $faker->randomElement(['txt', 'csv', 'pdf', 'docx', 'xlsx']);
    $filename = implode('.', [Str::random(), $ext]);

    return [
        'type' => $faker->randomElement(Attachment::TYPES),
        'filename' => $filename,
        'filepath' => implode(DIRECTORY_SEPARATOR, ['attachments', $filename]),
        'size' => mt_rand(1000, 2000),
        'extension' => $ext
    ];
});

$factory->state(Attachment::class, 'file', function (Faker $faker) {
    $ext = $faker->randomElement(['txt', 'csv', 'pdf', 'docx', 'xlsx']);
    $filename = implode('.', [Str::random(), $ext]);
    $size = mt_rand(1000, 2000); 

    return ['file' => UploadedFile::fake()->create($filename, $size)];
});
