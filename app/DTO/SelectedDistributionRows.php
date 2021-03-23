<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class SelectedDistributionRows extends DataTransferObject
{
    /**
     * @Constraints\Uuid()
     */
    public string $worldwide_distribution_id;

    /**
     * @Constraints\All(@Constraints\Uuid())
     *
     * @var string[]
     */
    public array $selected_rows;

    /**
     * @Constraints\All(@Constraints\Uuid())
     *
     * @var string[]
     */
    public array $selected_groups;

    public bool $reject = false;

    public bool $use_groups = false;

    /**
     * @Constraints\Choice({"product_no","description","serial_no","date_from","date_to","qty","price","pricing_document","system_handle","service_level_description","searchable",null})
     */
    public ?string $sort_rows_column;

    /**
     * @Constraints\Choice({"asc","desc"})
     */
    public string $sort_rows_direction = 'asc';

    /**
     * @Constraints\Choice({"group_name","search_text","rows_count","rows_sum",null})
     */
    public ?string $sort_rows_groups_column;

    /**
     * @Constraints\Choice({"asc","desc"})
     */
    public string $sort_rows_groups_direction = 'asc';
}
