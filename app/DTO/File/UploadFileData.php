<?php

namespace App\DTO\File;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Data;

class UploadFileData extends Data
{
    public function __construct(#[File] public readonly UploadedFile $file)
    {
    }

    public static function rules(...$args): array
    {
        return [
            'file' => [
                "mimes:".setting('supported_file_types_request'),
                "max:".setting('file_upload_size_kb'),
            ],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'file.max' => 'The allowed file upload maximum size is :max kb.',
            'file.mimes' => 'Unsupported file extension.',
        ];
    }
}