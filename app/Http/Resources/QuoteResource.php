<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * @var array
     */
    public $prepend = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge($this->prepend, [
            'pdf_file' => $this->when(($this->generatedPdf->original_file_path && $this->customer->rfq), function () {
                return route('s4.pdf', ['rfq' => $this->customer->rfq]);
            }),
            'price_list_file' => $this->when(($this->priceList->original_file_path && $this->customer->rfq), function () {
                return route('s4.price', ['rfq' => $this->customer->rfq]);
            }),
            'payment_schedule_file' => $this->when(($this->paymentSchedule->original_file_path && $this->customer->rfq), function () {
                return route('s4.schedule', ['rfq' => $this->customer->rfq]);
            }),
            'quote_data' => [
                'first_page' => [
                    'template_name' => $this->quoteTemplate->name,
                    'customer_name' => $this->customer->name,
                    'company_name' => $this->company->name,
                    'company_logo' => $this->company->logo,
                    'vendor_name' => $this->vendor->name,
                    'vendor_logo' => $this->vendor->logo,
                    'support_start' => $this->customer->support_start,
                    'support_end' => $this->customer->support_end,
                    'valid_until' => $this->customer->valid_until,
                    'quotation_number' => $this->customer->rfq,
                    'service_level' => $this->customer->service_level,
                    'list_price' => $this->list_price_formatted,
                    'applicable_discounts' => $this->applicable_discounts_formatted,
                    'final_price' => $this->final_price_formatted,
                    'payment_terms' => $this->customer->payment_terms,
                    'invoicing_terms' => $this->customer->invoicing_terms,
                    'full_name' => $this->user->full_name,
                    'date' => $this->updated_at
                ],
                'data_pages' => [
                    'pricing_document' => $this->pricing_document,
                    'service_agreement_id' => $this->service_agreement_id,
                    'system_handle' => $this->system_handle,
                    'equipment_address' => $this->customer->equipmentAddress->address_1,
                    'hardware_contact' => $this->customer->hardwareContact->contact_name,
                    'hardware_phone' => $this->customer->hardwareContact->phone,
                    'software_address' => $this->customer->softwareAddress->address_1,
                    'software_contact' => $this->customer->softwareContact->contact_name,
                    'software_phone' => $this->customer->softwareContact->phone,
                    'additional_details' => $this->additional_details,
                    'coverage_period' => $this->customer->coverage_period,
                    'coverage_period_from' => $this->customer->support_start,
                    'coverage_period_to' => $this->customer->support_end,
                    'rows_header' => $this->rowsHeaderToArray(),
                    'rows' => $this->computableRows
                ],
                'last_page' => [
                    'additional_details' => $this->additional_details
                ],
                'payment_schedule' => $this->when($this->scheduleData, function () {
                    return [
                        'company_name' => $this->company->name,
                        'vendor_name' => $this->vendor->name,
                        'customer_name' => $this->customer->name,
                        'support_start' => $this->customer->support_start,
                        'support_end' => $this->customer->support_end,
                        'period' => $this->customer->coverage_period,
                        'rows_header' => $this->scheduleData->rowsHeaderToArray(),
                        'total_payments' => $this->scheduleData->total_payments,
                        'data' => $this->scheduleData->value
                    ];
                })
            ]
        ]);
    }

    public function prepend(array $prepend)
    {
        $this->prepend = $prepend;
        return $this;
    }
}
