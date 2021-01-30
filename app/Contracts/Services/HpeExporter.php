<?php

namespace App\Contracts\Services;

use App\DTO\PreviewHpeContractData;
use App\Models\Template\HpeContractTemplate;
use Illuminate\Contracts\Support\Responsable;

interface HpeExporter
{
    /**
     * Export HPE Contract data.
     *
     * @param HpeContractTemplate $template
     * @param PreviewHpeContractData $data
     * @return Responsable
     */
    public function export(HpeContractTemplate $template, PreviewHpeContractData $data);

    /**
     * Retrieve arrayable HPE Contract Template images.
     *
     * @param HpeContractTemplate $template
     * @return array
     */
    public function retrieveTemplateImages(HpeContractTemplate $template): array;
}
