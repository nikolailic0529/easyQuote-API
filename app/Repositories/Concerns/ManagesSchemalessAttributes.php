<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Query\Builder;

trait ManagesSchemalessAttributes
{
    protected function unpivotJsonColumn(Builder $builder, $jsonColumn, $first, $match, $target, $as)
    {
        $jsonPath = static::jsonPathWhere($jsonColumn, $first, $match);

        $select = "NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(`$jsonColumn`, REPLACE({$jsonPath}, '{$first}', '{$target}')))), '') AS `{$as}`";

        return $builder->selectRaw($select);
    }

    protected function parseColumnDate(Builder $builder, $column, $default = null)
    {
        $whenNull = $default ? "IF(ISNULL(`{$column}`), {$default}, NULL)," : null;

        $excelDate = "IF(`{$column}` REGEXP '^[0-9]{5}$', DATE_ADD(DATE_ADD(DATE(IF(`{$column}` < 60, '1899-12-31', '1899-12-30')), INTERVAL FLOOR(`{$column}`) DAY), INTERVAL FLOOR(86400*(`{$column}`-FLOOR(`{$column}`))) SECOND), NULL)";

        return $builder->selectRaw("
            DATE_FORMAT(
                COALESCE(
                    {$whenNull}
                    STR_TO_DATE(`{$column}`, '%d.%m.%Y'),
                    STR_TO_DATE(`{$column}`, '%d/%m/%Y'),
                    STR_TO_DATE(`{$column}`, '%m/%d/%Y'),
                    STR_TO_DATE(`{$column}`, '%Y.%m.%d'),
                    STR_TO_DATE(`{$column}`, '%Y/%m/%d'),
                    STR_TO_DATE(`{$column}`, '%Y-%d-%m'),
                    {$excelDate},
                    {$default}
                ),
                '%d/%m/%Y'
            ) AS `{$column}`
        ");
    }

    protected static function jsonPathWhere(string $column, string $first, string $match)
    {
        return "JSON_UNQUOTE(JSON_SEARCH(`{$column}`, 'one', '{$match}', NULL, '$[*].{$first}'))";
    }

}
