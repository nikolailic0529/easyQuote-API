<?php

namespace App\Domain\HpeContract\Contracts;

use App\Domain\HpeContract\DataTransferObjects\PreviewHpeContractData;
use App\Domain\HpeContract\Models\HpeContractTemplate;

interface HpeExporter
{
    /**
     * Export HPE Contract data.
     *
     * @return mixed
     */
    public function export(HpeContractTemplate $template, PreviewHpeContractData $data);

    /**
     * Retrieve arrayable HPE Contract Template images.
     */
    public function retrieveTemplateImages(HpeContractTemplate $template): array;
}
