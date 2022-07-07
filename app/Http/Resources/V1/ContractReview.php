<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\ImportedRow\MappedRow;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ContractReview extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->customer->loadMissing('addresses', 'contacts');

        return [

                'first_page' => [
                    'template_name'         => $this->contractTemplate->name,
                    'customer_name'         => $this->customer_name,
                    'company_name'          => $this->company->name,
                    'company_logo'          => $this->company->logo,
                    'vendor_name'           => $this->vendor->name,
                    'vendor_logo'           => $this->vendor->logo,
                    'support_start'         => $this->when($this->isReview, $this->customer->support_start_date, $this->customer->support_start),
                    'support_end'           => $this->when($this->isReview, $this->customer->support_end_date, $this->customer->support_end),
                    'valid_until'           => $this->when($this->isReview, $this->customer->valid_until_date, $this->customer->valid_until),
                    'quotation_number'      => $this->customer->rfq,
                    'service_levels'        => $this->when($this->isReview, $this->customer->service_levels_formatted, $this->customer->service_levels),
                    'list_price'            => $this->list_price_formatted,
                    'applicable_discounts'  => $this->applicable_discounts_formatted,
                    'final_price'           => $this->final_price_formatted,
                    'invoicing_terms'       => $this->customer->invoicing_terms,
                    'full_name'             => $this->user->full_name,
                    'date'                  => $this->updated_at,
                    'service_agreement_id'  => $this->service_agreement_id,
                    'system_handle'         => $this->system_handle,
                ],
                'data_pages' => [
                    'pricing_document'      => $this->pricing_document,
                    'service_agreement_id'  => $this->service_agreement_id,
                    'system_handle'         => $this->system_handle,
                    'service_levels'        => $this->when($this->isReview, $this->customer->service_levels_formatted, $this->customer->service_levels),
                    'equipment_address'     => $this->customer->equipmentAddress->address_1,
                    'hardware_contact'      => $this->customer->equipmentAddress->contact_name,
                    'hardware_phone'        => $this->customer->equipmentAddress->contact_number,
                    'software_address'      => $this->customer->softwareAddress->address_1,
                    'software_contact'      => $this->customer->softwareAddress->contact_name,
                    'software_phone'        => $this->customer->softwareAddress->contact_number,
                    'additional_details'    => $this->when($this->isMode(QT_TYPE_QUOTE), $this->additional_details),
                    'coverage_period'       => $this->customer->coverage_period,
                    'coverage_period_from'  => $this->when($this->isReview, $this->customer->support_start_date, $this->customer->support_start),
                    'coverage_period_to'    => $this->when($this->isReview, $this->customer->support_end_date, $this->customer->support_end),
                    // 'rows_header'           => $this->when($this->isReview, $this->rowsHeaderToArray($this->systemHiddenFields), $this->rowsHeaderToArray()),
                    'rows'                  => MappedRow::collection(Collection::wrap($this->when($this->isReview, $this->renderableRows, $this->computableRows)))
                ],
                'last_page' => [
                    'additional_details'    => $this->additional_details
                ],
                'payment_schedule'          => $this->when($this->scheduleData, fn () =>
                    [
                        'company_name'      => $this->company->name,
                        'vendor_name'       => $this->vendor->name,
                        'customer_name'     => $this->customer->name,
                        'support_start'     => $this->when($this->isReview, $this->customer->support_start_date, $this->customer->support_start),
                        'support_end'       => $this->when($this->isReview, $this->customer->support_end_date, $this->customer->support_end),
                        'period'            => $this->customer->coverage_period,
                        // 'rows_header'       => $this->scheduleData->rowsHeaderToArray(),
                        'total_payments'    => $this->scheduleData->total_payments,
                        'data'              => $this->scheduleData->value
                    ]
                )
        ];
    }
}
