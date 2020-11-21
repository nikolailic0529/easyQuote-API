<?php

namespace App\Services;

use App\Models\{
    Quote\Quote,
    QuoteTemplate\BaseQuoteTemplate,
};
use App\Scopes\{NonVersionScope, QuoteTypeScope};

class RelationUsage
{
    public static function templateUsage(BaseQuoteTemplate $template): int
    {
        return Quote::query()->withoutGlobalScopes([NonVersionScope::class, QuoteTypeScope::class])
            ->where('quote_template_id', $template->getKey())
            ->count();
    }
}
