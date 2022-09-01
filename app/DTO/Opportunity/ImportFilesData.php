<?php

namespace App\DTO\Opportunity;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;

final class ImportFilesData extends Data
{
    public function __construct(
        #[File, Mimes('xlsx'), Max(10_000)]
        public readonly UploadedFile $opportunities_file,
        #[File, Mimes('xlsx'), Max(10_000)]
        public readonly ?UploadedFile $accounts_data_file,
        #[File, Mimes('xlsx'), Max(10_000)]
        public readonly ?UploadedFile $account_contacts_file
    ) {
    }
}
