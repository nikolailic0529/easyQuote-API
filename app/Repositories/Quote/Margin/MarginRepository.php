<?php namespace App\Repositories\Quote\Margin;

use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;

class MarginRepository implements MarginRepositoryInterface
{
    public function data(): array
    {
        $quote_types = __('quote.types');
        $margin_types = __('margin.types');

        return compact('quote_types', 'margin_types');
    }
}
