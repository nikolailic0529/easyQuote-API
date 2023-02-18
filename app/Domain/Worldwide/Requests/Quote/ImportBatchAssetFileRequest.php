<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\Quote\BatchAssetFileMapping;
use App\Domain\Worldwide\DataTransferObjects\Quote\ImportBatchAssetFileData;
use Illuminate\Foundation\Http\FormRequest;

class ImportBatchAssetFileRequest extends FormRequest
{
    protected ?ImportBatchAssetFileData $importData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'headers' => [
                'serial_no' => [
                    'bail', 'nullable', 'string',
                ],
                'sku' => [
                    'bail', 'nullable', 'string',
                ],
                'service_sku' => [
                    'bail', 'nullable', 'string',
                ],
                'product_name' => [
                    'bail', 'nullable', 'string',
                ],
                'expiry_date' => [
                    'bail', 'nullable', 'string',
                ],
                'service_level_description' => [
                    'bail', 'nullable', 'string',
                ],
                'price' => [
                    'bail', 'nullable', 'string',
                ],
                'selling_price' => [
                    'bail', 'nullable', 'string',
                ],
                'buy_price_value' => [
                    'bail', 'nullable', 'string',
                ],
                'buy_price_currency' => [
                    'bail', 'nullable', 'string',
                ],
                'buy_price_margin' => [
                    'bail', 'nullable', 'string',
                ],
                'exchange_rate_value' => [
                    'bail', 'nullable', 'string',
                ],
                'exchange_rate_margin' => [
                    'bail', 'nullable', 'string',
                ],
                'vendor' => [
                    'bail', 'nullable', 'string',
                ],
                'country' => [
                    'bail', 'nullable', 'string',
                ],
                'street_address' => [
                    'bail', 'nullable', 'string',
                ],
                'post_code' => [
                    'bail', 'nullable', 'string',
                ],
                'city' => [
                    'bail', 'nullable', 'string',
                ],
                'state' => [
                    'bail', 'nullable', 'string',
                ],
                'state_code' => [
                    'bail', 'nullable', 'string',
                ],
            ],
            'file_id' => [
                'bail', 'required', 'uuid',
            ],
            'file_contains_headers' => [
                'bail', 'nullable', 'boolean',
            ],
        ];
    }

    public function getImportData(): ImportBatchAssetFileData
    {
        return $this->importData ??= with(true, function () {
            $fileMapping = new BatchAssetFileMapping([
                'serial_no' => $this->input('headers.serial_no'),
                'sku' => $this->input('headers.sku'),
                'service_sku' => $this->input('headers.service_sku'),
                'product_name' => $this->input('headers.product_name'),
                'expiry_date' => $this->input('headers.expiry_date'),
                'service_level_description' => $this->input('headers.service_level_description'),
                'price' => $this->input('headers.price'),
                'selling_price' => $this->input('headers.selling_price'),
                'vendor' => $this->input('headers.vendor'),
                'country' => $this->input('headers.country'),
                'street_address' => $this->input('headers.street_address'),
                'post_code' => $this->input('headers.post_code'),
                'city' => $this->input('headers.city'),
                'state' => $this->input('headers.state'),
                'state_code' => $this->input('headers.state_code'),
                'buy_price_value' => $this->input('headers.buy_price_value'),
                'buy_price_currency' => $this->input('headers.buy_price_currency'),
                'buy_price_margin' => $this->input('headers.buy_price_margin'),
                'exchange_rate_value' => $this->input('headers.exchange_rate_value'),
                'exchange_rate_margin' => $this->input('headers.exchange_rate_margin'),
            ]);

            return new ImportBatchAssetFileData([
                'file_mapping' => $fileMapping,
                'file_id' => $this->input('file_id'),
                'file_contains_headers' => $this->boolean('file_contains_headers'),
            ]);
        });
    }
}
