<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class ElasticsearchHelper
{
    public static function escapeReservedChars(string $string): string
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";

        return preg_replace($regex, addslashes('\\$0'), $string);
    }

    public static function pluckDocumentKeys(?array $result): array
    {
        return data_get($result, 'hits.hits.*._id') ?? [];
    }
}