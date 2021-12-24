<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\BatchAssetFileMapping;
use App\DTO\WorldwideQuote\ImportBatchAssetFileData;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportBatchAssetFile extends FormRequest
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
                    'bail', 'nullable', 'string'
                ],
                'sku' => [
                    'bail', 'nullable', 'string'
                ],
                'service_sku' => [
                    'bail', 'nullable', 'string'
                ],
                'product_name' => [
                    'bail', 'nullable', 'string'
                ],
                'expiry_date' => [
                    'bail', 'nullable', 'string'
                ],
                'service_level_description' => [
                    'bail', 'nullable', 'string'
                ],
                'price' => [
                    'bail', 'nullable', 'string'
                ],
                'vendor' => [
                    'bail', 'nullable', 'string'
                ],
                'country' => [
                    'bail', 'nullable', 'string'
                ],
                'street_address' => [
                    'bail', 'nullable', 'string'
                ],
                'post_code' => [
                    'bail', 'nullable', 'string'
                ],
                'city' => [
                    'bail', 'nullable', 'string'
                ],
                'state' => [
                    'bail', 'nullable', 'string'
                ],
                'state_code' => [
                    'bail', 'nullable', 'string'
                ]
            ],
            'file_id' => [
                'bail', 'required', 'uuid'
            ],
            'file_contains_headers' => [
                'bail', 'nullable', 'boolean'
            ]
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
                'vendor' => $this->input('headers.vendor'),
                'country' => $this->input('headers.country'),
                'street_address' => $this->input('headers.street_address'),
                'post_code' => $this->input('headers.post_code'),
                'city' => $this->input('headers.city'),
                'state' => $this->input('headers.state'),
                'state_code' => $this->input('headers.state_code')
            ]);

            return new ImportBatchAssetFileData([
                'file_mapping' => $fileMapping,
                'file_id' => $this->input('file_id'),
                'file_contains_headers' => $this->boolean('file_contains_headers')
            ]);
        });
    }
}
