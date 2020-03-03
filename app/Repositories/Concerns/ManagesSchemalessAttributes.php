<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

trait ManagesSchemalessAttributes
{
    protected function unpivotJsonColumn(Builder $builder, $jsonColumn, $path, $as)
    {
        $select = "NULLIF(TRIM(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`$jsonColumn`, '{$path}')), 'null')), '') AS `{$as}`";

        return $builder->selectRaw($select);
    }

    protected function mapJsonPath(Builder $builder, $jsonColumn, $key, $value, $map, $target, $as)
    {
        $map = Collection::wrap($map);

        $index = $map->search(fn ($column) => data_get($column, $key) == $value);

        if ($index === false) {
            return $builder;
        }

        $path = "$[{$index}].{$target}";

        return $this->unpivotJsonColumn($builder, $jsonColumn, $path, $as);
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
