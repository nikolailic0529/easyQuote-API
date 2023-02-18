<?php

namespace App\Domain\HpeContract\Services;

use App\Domain\HpeContract\DataTransferObjects\HpeContractImportData;
use App\Domain\HpeContract\DataTransferObjects\ImportResponse;
use App\Domain\HpeContract\Imports\HpeContractDataImport;
use App\Domain\HpeContract\Models\HpeContractFile;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Filesystem\Factory as DiskFactory;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException;

class HpeContractFileService
{
    protected const DISK_NAME = 'hpe_contract_files';

    protected HpeContractFile $contractFile;

    protected Disk $disk;

    public function __construct(HpeContractFile $contractFile, DiskFactory $factory)
    {
        $this->contractFile = $contractFile;
        $this->disk = $factory->disk(static::DISK_NAME);
    }

    public function store(UploadedFile $file)
    {
        $filepath = $file->store('', ['disk' => static::DISK_NAME]);

        return DB::transaction(
            fn () => tap($this->contractFile->query()->make([
                'original_file_name' => $file->getClientOriginalName(),
                'original_file_path' => $filepath,
            ]))->save(),
            DB_TA
        );
    }

    public function processImport(HpeContractFile $hpeContractFile, HpeContractImportData $importData): ImportResponse
    {
        try {
            return DB::transaction(function () use ($importData, $hpeContractFile) {
                with($hpeContractFile, function (HpeContractFile $file) use ($importData) {
                    $file->hpeContractData()->delete();
                    $file->date_format = $importData->date_format;
                    $file->save();
                });

                (new HpeContractDataImport($hpeContractFile))
                    ->import(
                        $this->disk->path($hpeContractFile->original_file_path)
                    );

                $hpeContractFile->update(['imported_at' => now()]);

                return new ImportResponse(true);
            });
        } catch (InvalidFormatException $e) {
            return new ImportResponse(false, 'An invalid date format provided.');
        } catch (\Throwable $e) {
            customlog(['ErrorCode' => 'HPEC-IMPE-01'], ['ErrorDetails' => customlog()->formatError(HPEC_IMPE_01, $e)]);

            if ($e instanceof ValidationException) {
                /** @var Failure */
                $failure = head($e->failures());
                $error = head($failure->errors());

                return new ImportResponse(false, $error);
            }

            return new ImportResponse(false, HPEC_IMPE_01);
        }
    }
}
