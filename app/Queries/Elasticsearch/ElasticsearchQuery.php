<?php

namespace App\Queries\Elasticsearch;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class ElasticsearchQuery implements Arrayable
{
    const QST_BEST_FIELDS = 'best_fields';

    const QST_CROSS_FIELDS = 'cross_fields';

    const QST_MOST_FIELDS = 'most_fields';

    const QST_PHRASE = 'phrase';

    const QST_PHRASE_PREFIX = 'phrase_prefix';

    const QS_WILDCARD = '*';

    protected ?string $index = null;

    protected ?string $queryString = null;

    protected string $queryStringType = self::QST_CROSS_FIELDS;

    protected array $body = [];

    #[Pure]
    public static function new(): static
    {
        return new static();
    }

    public function index(string $index): ElasticsearchQuery
    {
        $this->index = $index;

        return $this;
    }

    public function modelIndex(Model $model): ElasticsearchQuery
    {
        if (method_exists($model, 'getSearchIndex')) {
            return $this->index($model->getSearchIndex());
        }

        return $this->index($model->getTable());
    }

    public function queryString(string $string, string $type = self::QST_PHRASE_PREFIX): ElasticsearchQuery
    {
        $this->queryString = $string;
        $this->queryStringType = $type;

        return $this;
    }

    public function escapeQueryString(): ElasticsearchQuery
    {
        $this->queryString = self::escapeReservedChars($this->queryString);

        return $this;
    }

    public function wrapQueryString(string $char = self::QS_WILDCARD): ElasticsearchQuery
    {
        $this->queryString = $char.$this->queryString.$char;

        return $this;
    }

    #[ArrayShape(['index' => "null|string", 'body' => "array|\array[][]"])]
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'body' => $this->buildBody(),
        ];
    }

    protected function buildBody(): array
    {
        if (is_null($this->queryString)) {
            return [];
        }

        return [
            'query' => [
                'query_string' => [
                    'query' => $this->queryString,
                    'type' => $this->queryStringType,
                ],
            ],
        ];

    }

    public static function escapeReservedChars(string $string): string
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";

        return preg_replace($regex, addslashes('\\$0'), $string);
    }
}
