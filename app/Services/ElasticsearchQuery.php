<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ElasticsearchQuery implements Arrayable
{
    const QST_BEST_FIELDS = 'best_fields';

    const QST_CROSS_FIELDS = 'cross_fields';

    const QST_MOST_FIELDS = 'most_fields';

    const QST_PHRASE = 'phrase';

    const QST_PHRASE_PREFIX = 'phrase_prefix';

    protected ?string $index = null;

    protected ?string $queryString = null;

    protected string $queryStringType = self::QST_CROSS_FIELDS;

    protected array $body = [];

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

    public function queryString(string $string, string $type = self::QST_CROSS_FIELDS): ElasticsearchQuery
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

    public function wrapQueryString(string $char = '*'): ElasticsearchQuery
    {
        $this->queryString = Str::finish(Str::start($this->queryString, $char), $char);

        return $this;
    }

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
                    'type' => $this->queryStringType
                ],
            ]
        ];

    }

    public static function escapeReservedChars(string $string): string
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";

        return preg_replace($regex, addslashes('\\$0'), $string);
    }
}
