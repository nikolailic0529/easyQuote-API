<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrateContractsToContractsTable extends Migration
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
                ->joinSub(
                    DB::table('customers')->select('id', 'rfq', 'name')->whereNull('deleted_at'),
                    'customers',
                    function (JoinClause $join) {
                        $join->on('customers.id', '=', 'quotes.customer_id')->limit(1);
                    }
                )
                ->select(
                    'quotes.id',
                    'quotes.user_id',
                    'quotes.quote_id',
                    'quotes.customer_id',
                    'quotes.company_id',
                    'quotes.vendor_id',
                    'quotes.country_id',
                    'quotes.contract_template_id',
                    'quotes.distributor_file_id',
                    'quotes.schedule_file_id',
                    'customers.rfq as customer_rfq',
                    'customers.name as customer_name',
                    'quotes.completeness',
                    'quotes.group_description',
                    'quotes.use_groups',
                    'quotes.pricing_document',
                    'quotes.service_agreement_id',
                    'quotes.system_handle',
                    'quotes.additional_notes',
                    'quotes.closing_date',
                    'quotes.previous_state',
                    'quotes.created_at',
                    'quotes.updated_at',
                    'quotes.deleted_at',
                    'quotes.submitted_at',
                    'quotes.activated_at'
                )

                ->where('document_type', 2)
                ->orderBy('quotes.id')
                ->chunk(100, function (Collection $chunk) {
                    $rows = $chunk->map(fn (object $contract) => [
                        'id' => $contract->id,
                        'user_id' => $contract->user_id,
                        'quote_id' => $contract->quote_id,
                        'customer_id' => $contract->customer_id,
                        'company_id' => $contract->company_id,
                        'vendor_id' => $contract->vendor_id,
                        'country_id' => $contract->country_id,
                        'contract_template_id' => $contract->contract_template_id,

                        'distributor_file_id' => $contract->distributor_file_id,
                        'schedule_file_id' => $contract->schedule_file_id,

                        'contract_number' => Str::replaceFirst('CQ', 'CT', $contract->customer_rfq),
                        'customer_name' => $contract->customer_name,
                        'completeness' => $contract->completeness,
                        'group_description' => $contract->group_description,
                        'use_groups' => $contract->use_groups,
                        'pricing_document' => $contract->pricing_document,
                        'service_agreement_id' => $contract->service_agreement_id,
                        'system_handle' => $contract->system_handle,
                        'additional_notes' => $contract->additional_notes,
                        'contract_date' => $contract->closing_date,
                        'previous_state' => $contract->previous_state,

                        'created_at' => $contract->created_at,
                        'updated_at' => $contract->updated_at,
                        'deleted_at' => $contract->deleted_at,
                        'submitted_at' => $contract->submitted_at,
                        'activated_at' => $contract->activated_at,
                    ])->all();

                    DB::table('contracts')->insert($rows);
                });


            $mappingPivot = DB::table('quote_field_column')
                ->whereIn('quote_id', DB::table('contracts')->select('id'))
                ->get();

            $mappingPivot = $mappingPivot->map(fn ($pivot) => [
                'contract_id' => $pivot->quote_id,
                'template_field_id' => $pivot->template_field_id,
                'importable_column_id' => $pivot->importable_column_id,
                'is_default_enabled' => $pivot->is_default_enabled,
                'default_value' => $pivot->default_value,
                'is_preview_visible' => $pivot->is_preview_visible,
                'sort' => $pivot->sort,
            ])->all();

            foreach (array_chunk($mappingPivot, 100) as $mappingChunk) {
                DB::table('contract_field_column')->insert($mappingChunk);
            }

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
