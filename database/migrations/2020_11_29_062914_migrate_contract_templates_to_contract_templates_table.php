<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MigrateContractTemplatesToContractTemplatesTable extends Migration
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
            /*
             * Migrate quote_templates with type = 2 to contract_templates table.
             */
            DB::table('quote_templates')
                ->where('type', 2)
                ->orderBy('id')
                ->chunk(100, function (Collection $chunk) {
                    $rows = $chunk->map(fn ($template) => [
                        'id' => $template->id,

                        'user_id' => $template->user_id,
                        'company_id' => $template->company_id,
                        'vendor_id' => $template->vendor_id,
                        'currency_id' => $template->currency_id,

                        'name' => $template->name,
                        'is_system' => $template->is_system,
                        'form_data' => $template->form_data,
                        'data_headers' => $template->data_headers,

                        'created_at' => $template->created_at,
                        'updated_at' => $template->updated_at,
                        'deleted_at' => $template->deleted_at,
                        'activated_at' => $template->activated_at,
                    ])->all();

                    DB::table('contract_templates')->insert($rows);
                });

            /**
             * Migrate country pivot table.
             */
            $countryPivot = DB::table('country_quote_template')
                ->select('country_id', 'quote_template_id as contract_template_id')
                ->whereIn('quote_template_id', DB::table('contract_templates')->select('id'))
                ->get();

            $countryPivot = $countryPivot->map(fn ($pivot) => (array) $pivot)->all();

            DB::table('country_contract_template')->insert($countryPivot);
        } catch (Throwable $e) {
            DB::commit();

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
    }
}
