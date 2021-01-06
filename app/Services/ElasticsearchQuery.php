<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class ElasticsearchQuery implements Arrayable
{
    /** @var string */
    protected $index;

    /** @var array */
    protected $body;

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

    public function queryString(string $string): ElasticsearchQuery
    {
        $this->body = [
            'query' => [
                'query_string' => [
                    'query' => $string,
                ],
            ]
        ];

        return $this;
    }

    public function toArray()
    {
        return [
            'index' => $this->index,
            'body' => $this->body,
        ];
    }

    public static function escapeReservedChars(string $string)
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";

        return preg_replace($regex, addslashes('\\$0'), $string);
    }
}