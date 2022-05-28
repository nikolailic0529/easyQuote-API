<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Models\Address;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteOpportunity extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Opportunity|QuoteOpportunity $this */
        return [
            'id' => $this->getKey(),

            'pipeline_id' => $this->pipeline_id,
            'pipeline' => $this->whenLoaded('pipeline'),

            'account_manager' => $this->whenLoaded('accountManager'),
            'primary_account' => $this->whenLoaded('primaryAccount', function () {

                /** @var Opportunity|QuoteOpportunity $this */

                if ($this->primaryAccount->relationLoaded('addresses')) {
                    $this->primaryAccount->addresses->each(function (Address $address) {
                        $address->setAttribute('is_default', $address->pivot->is_default);
                    });
                }

                if ($this->primaryAccount->relationLoaded('contacts')) {
                    $this->primaryAccount->contacts->each(function (Contact $contact) {
                        $contact->setAttribute('is_default', $contact->pivot->is_default);
                    });
                }

                return $this->primaryAccount;

            }),
            'primary_account_contact' => $this->whenLoaded('primaryAccountContact'),
            'end_user' => $this->whenLoaded('endUser', function () {

                /** @var Opportunity|QuoteOpportunity $this */

                if ($this->endUser->relationLoaded('addresses')) {
                    $this->endUser->addresses->each(function (Address $address) {
                        $address->setAttribute('is_default', $address->pivot->is_default);
                    });
                }

                if ($this->endUser->relationLoaded('contacts')) {
                    $this->endUser->contacts->each(function (Contact $contact) {
                        $contact->setAttribute('is_default', $contact->pivot->is_default);
                    });
                }

                return $this->endUser;

            }),

            'merged_addresses' => value(function (): Collection {

                /** @var Opportunity|QuoteOpportunity $this */

                $addresses = new Collection();

                if ($this->relationLoaded('primaryAccount') && $this->primaryAccount?->relationLoaded('addresses')) {
                    $addresses = $addresses->merge($this->primaryAccount->addresses);
                }

                if ($this->relationLoaded('endUser') && $this->endUser?->relationLoaded('addresses')) {
                    $addresses = $addresses->merge($this->endUser->addresses);
                }

                if (isset($this->additional['addresses'])) {
                    $addresses = $addresses->merge($this->additional['addresses']);
                }

                $addresses->each(function (Address $address) {
                    $address->setAttribute('address_string', WorldwideQuoteDataMapper::formatAddressToString($address));
                });

                return $addresses;

            }),

            'merged_contacts' => value(function (): Collection {

                /** @var Opportunity|QuoteOpportunity $this */

                $contacts = new Collection();

                if ($this->relationLoaded('primaryAccount') && $this->primaryAccount?->relationLoaded('contacts')) {
                    $contacts = $contacts->merge($this->primaryAccount->contacts);
                }

                if ($this->relationLoaded('endUser') && $this->endUser?->relationLoaded('contacts')) {
                    $contacts = $contacts->merge($this->endUser->contacts);
                }

                if (isset($this->additional['contacts'])) {
                    $contacts = $contacts->merge($this->additional['contacts']);
                }

                return $contacts;

            }),

            'addresses' => $this->when(isset($this->additional['addresses']), function () {
                return $this->additional['addresses'];
            }),
            'contacts' => $this->when(isset($this->additional['contacts']), function () {
                return $this->additional['contacts'];
            }),
            'project_name' => $this->project_name,
            'nature_of_service' => $this->nature_of_service,
            'sale_action_name' => $this->sale_action_name,
            'customer_status' => $this->customer_status,
            'end_user_name' => $this->end_user_name,
            'hardware_status' => $this->hardware_status,
            'region_name' => $this->region_name,
            'account_manager_name' => $this->account_manager_name,
            'service_level_agreement_id' => $this->service_level_agreement_id,
            'sale_unit_name' => $this->sale_unit_name,
            'competition_name' => $this->competition_name,
            'drop_in' => $this->drop_in,
            'lead_source_name' => $this->lead_source_name,
            'opportunity_amount' => $this->opportunity_amount,
            'opportunity_amount_currency_code' => $this->opportunity_amount_currency_code,
            'purchase_price' => $this->purchase_price,
            'purchase_price_currency_code' => $this->purchase_price_currency_code,
            'list_price' => $this->list_price,
            'list_price_currency_code' => $this->list_price_currency_code,
            'estimated_upsell_amount' => $this->estimated_upsell_amount,
            'estimated_upsell_amount_currency_code' => $this->estimated_upsell_amount_currency_code,
            'margin_value' => $this->margin_value,
            'renewal_month' => $this->renewal_month,
            'renewal_year' => $this->renewal_year,
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_closing_date' => $this->opportunity_closing_date,
            'expected_order_date' => $this->expected_order_date,
            'customer_order_date' => $this->customer_order_date,
            'purchase_order_date' => $this->purchase_order_date,
            'supplier_order_date' => $this->supplier_order_date,
            'supplier_order_transaction_date' => $this->supplier_order_transaction_date,
            'supplier_order_confirmation_date' => $this->supplier_order_confirmation_date,
            'has_higher_sla' => $this->has_higher_sla,
            'is_multi_year' => $this->is_multi_year,
            'has_additional_hardware' => $this->has_additional_hardware,
            'has_service_credits' => $this->has_service_credits,
            'personal_rating' => $this->personal_rating,
            'ranking' => $this->ranking,
            'remarks' => $this->remarks,
            'notes' => $this->notes,
        ];
    }
}
