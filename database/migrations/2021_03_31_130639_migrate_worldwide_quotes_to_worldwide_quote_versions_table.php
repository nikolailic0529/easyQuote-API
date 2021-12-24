<?php

use Illuminate\Database\Migrations\Migration;

class MigrateWorldwideQuotesToWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $worldwideQuotes = DB::table('worldwide_quotes')
            ->get();

        DB::transaction(function () use ($worldwideQuotes) {
            foreach ($worldwideQuotes as $quote) {
                $versionId = (string)\Webpatser\Uuid\Uuid::generate(4);

                DB::table('worldwide_quote_versions')
                    ->insert([
                        'id' => $versionId,
                        'worldwide_quote_id' => $quote->id,
                        'user_id' => $quote->user_id,
                        'company_id' => $quote->company_id,
                        'quote_template_id' => $quote->quote_template_id,
                        'quote_currency_id' => $quote->quote_currency_id,
                        'output_currency_id' => $quote->output_currency_id,
                        'multi_year_discount_id' => $quote->multi_year_discount_id,
                        'pre_pay_discount_id' => $quote->pre_pay_discount_id,
                        'promotional_discount_id' => $quote->promotional_discount_id,
                        'sn_discount_id' => $quote->sn_discount_id,
                        'custom_discount' => $quote->custom_discount,
                        'user_version_sequence_number' => 1,
                        'quote_type' => $quote->quote_type,
                        'margin_value' => $quote->margin_value,
                        'margin_method' => $quote->margin_method,
                        'tax_value' => $quote->tax_value,
                        'buy_price' => $quote->buy_price,
                        'sort_rows_column' => $quote->sort_rows_column,
                        'sort_rows_direction' => $quote->sort_rows_direction,
                        'exchange_rate_margin' => $quote->exchange_rate_margin,
                        'quote_expiry_date' => $quote->quote_expiry_date,
                        'payment_terms' => $quote->payment_terms,
                        'pricing_document' => $quote->pricing_document,
                        'service_agreement_id' => $quote->service_agreement_id,
                        'system_handle' => $quote->system_handle,
                        'additional_details' => $quote->additional_details,
                        'additional_notes' => $quote->additional_notes,
                        'completeness' => $quote->completeness,
                        'closing_date' => $quote->closing_date,
                        'created_at' => $quote->created_at,
                        'updated_at' => $quote->updated_at,
                        'deleted_at' => $quote->deleted_at,
                        'assets_migrated_at' => $quote->assets_migrated_at
                    ]);

                DB::table('worldwide_quote_assets')
                    ->where('worldwide_quote_id', $quote->id)
                    ->update([
                        'worldwide_quote_id' => $versionId,
                        'worldwide_quote_type' => '9d7c91c4-5308-4a40-b49e-f10ae552e480'
                    ]);

                DB::table('worldwide_distributions')
                    ->where('worldwide_quote_id', $quote->id)
                    ->update([
                        'worldwide_quote_id' => $versionId,
                        'worldwide_quote_type' => '9d7c91c4-5308-4a40-b49e-f10ae552e480'
                    ]);

                DB::table('worldwide_quotes')
                    ->where('id', $quote->id)
                    ->update(['active_version_id' => $versionId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
