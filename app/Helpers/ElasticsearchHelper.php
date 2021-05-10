<?php

namespace App\Helpers;

class ElasticsearchHelper
{
    public static function escapeReservedChars(string $string): string
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";

        return preg_replace($regex, addslashes('\\$0'), $string);
    }

    public static function pluckDocumentKeys(array $result): array
    {
        return data_get($result, 'hits.hits.*._id') ?? [];
    }
}