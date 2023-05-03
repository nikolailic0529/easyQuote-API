<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateSchemaOfSalesOrderTemplateData extends DataTransferObject
{
    public array $form_data;

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateDataHeader[]
     */
    public array $data_headers;
}
