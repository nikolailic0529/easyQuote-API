<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        /** @var Company $primaryAccount */
        $primaryAccount = Company::factory()->create();
        /** @var Company $endCustomer */
        $endCustomer = Company::factory()->create();

        return [
            'sales_unit_id' => SalesUnit::factory(),
            'pipeline_id' => PL_WWDP,
            'pipeline_stage_id' => PipelineStage::query()->where('pipeline_id', PL_WWDP)->first()->getKey(),
            'contract_type_id' => $this->faker->randomElement([CT_CONTRACT, CT_PACK]),
            'primary_account_id' => $primaryAccount->getKey(),
            'end_user_id' => $endCustomer->getKey(),
            'primary_account_contact_id' => Contact::factory()->create()->getKey(),
            'account_manager_id' => $user->getKey(),

            'project_name' => $this->faker->text(40),

            'nature_of_service' => Str::random(40),
            'renewal_month' => $this->faker->randomElement([
                'TBC_Pack',
                '01_Jan',
                '02_Feb',
                '03_Mar',
                '04_Apr',
                '05_May',
                '06_Jun',
                '06_Jul',
                '08_Aug',
                '09_Sep',
                '10_Oct',
                '11_Nov',
                '12_Dec',
            ]),
            'renewal_year' => mt_rand(2020, 2100),
            'customer_status' => Str::random(40),
            'end_user_name' => Str::random(40),
            'hardware_status' => Str::random(40),
            'region_name' => Str::random(40),

            'opportunity_start_date' => $this->faker->dateTimeBetween('-60 days')->format('Y-m-d'),
            'opportunity_end_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'opportunity_closing_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'customer_order_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'purchase_order_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'supplier_order_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'supplier_order_transaction_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'supplier_order_confirmation_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'expected_order_date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),

            'opportunity_amount' => (string)$this->faker->randomFloat(2, 1000, 10000),
            'base_opportunity_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'opportunity_amount_currency_code' => $this->faker->currencyCode,
            'purchase_price' => (string)$this->faker->randomFloat(2, 1000, 10000),
            'base_purchase_price' => $this->faker->randomFloat(2, 1000, 10000),
            'purchase_price_currency_code' => $this->faker->currencyCode,
            'list_price' => (string)$this->faker->randomFloat(2, 1000, 10000),
            'base_list_price' => $this->faker->randomFloat(2, 1000, 10000),
            'list_price_currency_code' => $this->faker->currencyCode,
            'estimated_upsell_amount' => (string)$this->faker->randomFloat(2, 100, 1000),
            'estimated_upsell_amount_currency_code' => $this->faker->currencyCode,
            'margin_value' => (string)$this->faker->randomFloat(2, 10, 90),

            'account_manager_name' => $this->faker->name,
            'service_level_agreement_id' => Str::random(40),
            'sale_unit_name' => Str::random(40),
            'competition_name' => $this->faker->text(191),
            'drop_in' => $this->faker->text(191),
            'lead_source_name' => $this->faker->randomElement([
                '1 - Vendor Renewal',
                '2 - Conversion',
                '3 - Customer request',
                '4 - Vendor Lead',
                '5 - Employee',
            ]),

            'has_higher_sla' => $this->faker->boolean,
            'is_multi_year' => $this->faker->boolean,
            'has_additional_hardware' => $this->faker->boolean,
            'has_service_credits' => $this->faker->boolean,

            'remarks' => $this->faker->text(10000),
            'notes' => $this->faker->text(10000),
            'personal_rating' => $this->faker->randomElement([
                '1 - Need not confirmed',
                '2 - Need is confirmed',
                '3 - Good chance',
                '4 - Strong selling signals',
                '5 - Will be ordered',
            ]),
            'ranking' => $this->faker->randomElement([0, 20, 40, 60, 80, 100]),

            'sale_action_name' => $this->faker->randomElement([
                "Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "PO Placed", "Processed in MC", "Closed",
            ]),
        ];
    }

    public function imported(): static
    {
        return $this->state(['deleted_at' => now()]);
    }
}

