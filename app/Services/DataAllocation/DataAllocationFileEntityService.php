<?php

namespace App\Services\DataAllocation;

use App\DTO\File\UploadFileData;
use App\Models\DataAllocation\DataAllocationFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface;

class DataAllocationFileEntityService
{
    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Filesystem $filesystem)
    {
    }

    public function createDataAllocationFile(UploadFileData $data): DataAllocationFile
    {
        return tap(new DataAllocationFile(), function (DataAllocationFile $model) use ($data): void {
            $model->filepath = $this->filesystem->put('', $data->file);
            $model->filename = $data->file->getClientOriginalName();
            $model->extension = $data->file->getClientOriginalExtension();
            $model->size = $data->file->getSize();

            $this->connectionResolver->connection()->transaction(static fn () => $model->save());
        });
    }
}