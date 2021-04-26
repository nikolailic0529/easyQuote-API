<?php

namespace App\DTO\SalesOrderTemplate;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateSchemaOfSalesOrderTemplateData extends DataTransferObject
{
    public array $form_data;

    public array $data_headers;
}
