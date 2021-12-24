<?php

namespace App\DTO\SalesOrderTemplate;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateSchemaOfSalesOrderTemplateData extends DataTransferObject
{
    public array $form_data;

    /**
     * @var \App\DTO\SalesOrderTemplate\TemplateDataHeader[]
     */
    public array $data_headers;
}
