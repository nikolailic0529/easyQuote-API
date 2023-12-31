<?php

namespace App\Domain\ExchangeRate\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ConvertCurrencyResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'from_currency_code' => $this->resource['from_currency_code'],
            'to_currency_code' => $this->resource['to_currency_code'],
            'exchange_date' => $this->resource['exchange_date'],
            'amount' => $this->resource['amount'],
            'result' => round($this->resource['result'], precision: 8),
            'result_formatted' => sprintf('%s %s', $this->resource['to_currency_symbol'],
                number_format((float) $this->resource['result'], 2)),
        ];
    }
}
