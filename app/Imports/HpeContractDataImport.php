<?php

namespace App\Imports;

use App\Models\HpeContractData;
use App\Models\HpeContractFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use voku\helper\ASCII;

class HpeContractDataImport implements ToModel, WithBatchInserts, WithChunkReading, WithCustomCsvSettings, WithStartRow, WithCustomValueBinder
{
    use Importable;

    const CELL_AMPID = 0;
    const CELL_SAR = 3;
    const CELL_CN = 4;
    const CELL_CSD = 6;
    const CELL_CED = 7;
    const CELL_PR = 8;
    const CELL_ORDAU = 9;
    const CELL_PN = 11;
    const CELL_PD = 12;
    const CELL_PQ = 13;
    const CELL_SC = 14;
    const CELL_SD = 15;
    const CELL_SC2 = 16;
    const CELL_SD2 = 17;
    const CELL_SL = 18;
    const CELL_SN = 21;
    const CELL_ST = 23;
    const CELL_AT = 22;
    const CELL_HWDCN = 24;
    const CELL_HWDCP = 25;
    const CELL_SWDCN = 26;
    const CELL_SWDCP = 27;
    const CELL_PRSCN = 28;
    const CELL_PRSCP = 29;
    const CELL_CSN = 30;
    const CELL_CSA = 31;
    const CELL_CSC = 32;
    const CELL_CSPC = 34;
    const CELL_CSTC = 35;
    const CELL_SSD = 41;
    const CELL_SED = 42;
    const CELL_RLN = 48;
    const CELL_RLA = 49;
    const CELL_RLS = 50;
    const CELL_RLC = 52;
    const CELL_RLPC = 53;

    protected HpeContractFile $hpeContractFile;

    protected ?string $supportAccountRef = null;

    public function __construct(HpeContractFile $hpeContractFile)
    {
        $this->hpeContractFile = $hpeContractFile;

        // HeadingRowFormatter::
        //     default('none');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $row = optional($row);

        /**
         * When new Support Account Reference is present in the line, we will assume to use it in the next rows.
         */
        if (filled($row[static::CELL_SAR])) {
            $this->supportAccountRef = $row[static::CELL_SAR];
        }

        $attributes = [
            'hpe_contract_file_id' => $this->hpeContractFile->getKey(),
            'amp_id' => $row[static::CELL_AMPID],
            'support_account_reference' => $this->supportAccountRef,
            'contract_number' => $row[static::CELL_CN],
            'contract_start_date' => static::parseDate($row[static::CELL_CSD]),
            'contract_end_date' => static::parseDate($row[static::CELL_CED]),
            'price' => (float)$row[static::CELL_PR],
            'order_authorization' => $row[static::CELL_ORDAU],
            'asset_type' => $row[static::CELL_AT],
            'product_number' => $row[static::CELL_PN],
            'product_description' => $row[static::CELL_PD],
            'product_quantity' => (int)$row[static::CELL_PQ],
            'service_code' => $row[static::CELL_SC],
            'service_description' => $row[static::CELL_SD],
            'service_code_2' => $row[static::CELL_SC2],
            'service_description_2' => $row[static::CELL_SD2],
            'service_levels' => $row[static::CELL_SL],
            'serial_number' => $row[static::CELL_SN],
            'service_type' => $row[static::CELL_ST],
            'hw_delivery_contact_name' => $row[static::CELL_HWDCN],
            'hw_delivery_contact_phone' => $row[static::CELL_HWDCP],
            'sw_delivery_contact_name' => $row[static::CELL_SWDCN],
            'sw_delivery_contact_phone' => $row[static::CELL_SWDCP],
            'pr_support_contact_name' => $row[static::CELL_PRSCN],
            'pr_support_contact_phone' => $row[static::CELL_PRSCP],

            'customer_name' => $row[static::CELL_CSN],
            'customer_address' => $row[static::CELL_CSA],
            'customer_city' => $row[static::CELL_CSC],
            'customer_post_code' => $row[static::CELL_CSPC],
            'customer_state_code' => $row[static::CELL_CSTC],

            'reseller_name' => $row[static::CELL_RLN],
            'reseller_address' => $row[static::CELL_RLA],
            'reseller_city' => $row[static::CELL_RLC],
            'reseller_post_code' => $row[static::CELL_RLPC],
            'reseller_state' => $row[static::CELL_RLS],

            'support_start_date' => $this->parseDate($row[static::CELL_SSD]),
            'support_end_date' => $this->parseDate($row[static::CELL_SED]),
        ];

        return new HpeContractData($attributes);
    }

    public function rules(): array
    {
        return [
            static::CELL_AMPID => 'present',
            static::CELL_SAR => 'present',
            static::CELL_CN => 'present',
            static::CELL_CSD => 'present',
            static::CELL_CED => 'present',
            static::CELL_ORDAU => 'present',
            static::CELL_PN => 'present',
            static::CELL_SC => 'present',
            static::CELL_HWDCN => 'present',
            static::CELL_HWDCP => 'present',
            static::CELL_SWDCN => 'present',
            static::CELL_SWDCP => 'present',
            static::CELL_PRSCN => 'present',
            static::CELL_PRSCP => 'present',
            static::CELL_CSN => 'present',
            static::CELL_CSA => 'present',
            static::CELL_CSC => 'present',
            static::CELL_SSD => 'present',
            static::CELL_SED => 'present'
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            static::CELL_AMPID.'.present' => 'AMP ID Column must be present.',
            static::CELL_SAR.'.present' => 'Support Account Reference Column must be present.',
            static::CELL_CN.'.present' => 'Contract Number Columns must be present.',
            static::CELL_CSD.'.present' => 'Contract Start Date Column must be present.',
            static::CELL_CED.'.present' => 'Contract End Date Column must be present.',
            static::CELL_ORDAU.'.present' => 'Order Authorization Column must be present.',
            static::CELL_PN.'.present' => 'Product Number Column must be present.',
            static::CELL_SC.'.present' => 'Service Code Column must be present.',
            static::CELL_HWDCN.'.present' => 'HW Delivery Contact Name Column must be present.',
            static::CELL_HWDCP.'.present' => 'HW Delivery Contact Phone Column must be present.',
            static::CELL_SWDCN.'.present' => 'SW Delivery Contact Name Column must be present.',
            static::CELL_SWDCP.'.present' => 'SW Delivery Contact Phone Column must be present.',
            static::CELL_PRSCN.'.present' => 'Primary Support Recipient Name Column must be present.',
            static::CELL_PRSCP.'.present' => 'Primary Support Recipient Phone Column must be present.',
            static::CELL_CSN.'.present' => 'Customer Name Column must be present.',
            static::CELL_CSA.'.present' => 'Customer Address Column must be present.',
            static::CELL_CSC.'.present' => 'Customer City Column must be present.',
            static::CELL_SSD.'.present' => 'Support Start Date Column must be present.',
            static::CELL_SED.'.present' => 'Support End Date Column must be present.',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => "\t",
            'use_bom' => Reader::BOM_UTF8,
        ];
    }

    public function startRow(): int
    {
        return 1;
    }

    /**
     * Bind value to a cell.
     *
     * @param Cell $cell Cell to bind value to
     * @param mixed $value Value to bind in cell
     *
     * @return bool
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            if (!ASCII::is_ascii($value)) {
                $value = ASCII::to_ascii(utf8_encode($value));
            }

            $value = StringHelper::sanitizeUTF8($value);
        }

        $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);

        return true;
    }

    private function parseDate(?string $value): ?string
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        if (!is_null($this->hpeContractFile->date_format)) {
            return Carbon::createFromFormat($this->hpeContractFile->date_format, $value)->toDateString();
        }

        if (str_contains($value, '/')) {
            $value = str_replace('/', '-', $value);
        }

        return Carbon::parse($value)->toDateString();
    }
}
