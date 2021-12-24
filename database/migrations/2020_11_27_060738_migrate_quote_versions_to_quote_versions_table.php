<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpParser\Node\Stmt\Catch_;

class MigrateQuoteVersionsToQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();

        try {
            DB::table('quotes')
                ->select([
                    'id',
                    // 'quote_id',
                    'distributor_file_id',
                    'schedule_file_id',
                    'user_id',
                    'quote_template_id',
                    'company_id',
                    'vendor_id',
                    'country_id',
                    'source_currency_id',
                    'target_currency_id',
                    'customer_id',
                    'previous_state',
                    'country_margin_id',
                    'completeness',
                    'pricing_document',
                    'service_agreement_id',
                    'system_handle',
                    'additional_details',
                    'checkbox_status',
                    'closing_date',
                    'additional_notes',
                    'calculate_list_price',
                    'buy_price',
                    'exchange_rate_margin',
                    'custom_discount',
                    'group_description',
                    'use_groups',
                    'sort_group_description',
                    'version_number',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'assets_migrated_at',
                ])
                ->addSelect(['quote_id' => DB::table('quote_version')->select('quote_id')->whereColumn('quote_version.version_id', 'quotes.id')->limit(1)])
                ->where('document_type', 1)
                ->where('is_version', true)
                ->orderBy('id')
                ->chunk(100, function (Collection $chunk) {
                    $rows = $chunk->map(fn (object $quoteVersion) => [
                        'id' => $quoteVersion->id,
                        'quote_id' => $quoteVersion->quote_id,

                        'distributor_file_id' => $quoteVersion->distributor_file_id,
                        'schedule_file_id' => $quoteVersion->schedule_file_id,

                        'user_id' => $quoteVersion->user_id,
                        'quote_template_id' => $quoteVersion->quote_template_id,
                        'company_id' => $quoteVersion->company_id,
                        'vendor_id' => $quoteVersion->vendor_id,
                        'country_id' => $quoteVersion->country_id,
                        'source_currency_id' => $quoteVersion->source_currency_id,
                        'target_currency_id' => $quoteVersion->target_currency_id,
                        // 'language_id' ??
                        'customer_id' => $quoteVersion->customer_id,
                        'previous_state' => $quoteVersion->previous_state,
                        'country_margin_id' => $quoteVersion->country_margin_id,
                        'completeness' => $quoteVersion->completeness,
                        // 'margin_data' ??
                        'pricing_document' => $quoteVersion->pricing_document,
                        'service_agreement_id' => $quoteVersion->service_agreement_id,
                        'system_handle' => $quoteVersion->system_handle,
                        'additional_details' => $quoteVersion->additional_details,
                        'checkbox_status' => $quoteVersion->checkbox_status,
                        'closing_date' => $quoteVersion->closing_date,
                        'additional_notes' => $quoteVersion->additional_notes,
                        'calculate_list_price' => $quoteVersion->calculate_list_price,
                        'buy_price' => $quoteVersion->buy_price,
                        'exchange_rate_margin' => $quoteVersion->exchange_rate_margin,
                        'custom_discount' => $quoteVersion->custom_discount,
                        'group_description' => $quoteVersion->group_description,
                        'use_groups' => $quoteVersion->use_groups,
                        'sort_group_description' => $quoteVersion->sort_group_description,
                        'version_number' => $quoteVersion->version_number,

                        'created_at' => $quoteVersion->created_at,
                        'updated_at' => $quoteVersion->updated_at,
                        'deleted_at' => $quoteVersion->deleted_at,
                        'assets_migrated_at' => $quoteVersion->assets_migrated_at,
                    ])->all();

                    DB::table('quote_versions')->insert($rows);
                });

            $discountsPivot = DB::table('quote_discount')
                ->whereIn('quote_id', DB::table('quote_versions')->select('id'))
                ->get();

            $discountsPivot = $discountsPivot->map(fn ($discount) => [
                'quote_version_id' => $discount->quote_id,
                'discount_id' => $discount->discount_id,
                'duration' => $discount->duration,
            ])->all();

            DB::table('quote_version_discount')->insert($discountsPivot);

            $mappingPivot = DB::table('quote_field_column')
                ->whereIn('quote_id', DB::table('quote_versions')->select('id'))
                ->get();

            $mappingPivot = $mappingPivot->map(fn ($mapping) => [
                'quote_version_id' => $mapping->quote_id,
                'template_field_id' => $mapping->template_field_id,
                'importable_column_id' => $mapping->importable_column_id,
                'is_default_enabled' => $mapping->is_default_enabled,
                'default_value' => $mapping->default_value,
                'is_preview_visible' => $mapping->is_preview_visible,
                'sort' => $mapping->sort,
            ])->all();

            DB::table('quote_version_field_column')->insert($mappingPivot);
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();
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
