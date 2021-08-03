<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateCompanyReferenceIdCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customers = DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->join('companies', function (\Illuminate\Database\Query\JoinClause $join) {
                $join->on('companies.name', 'customers.name')
                    ->whereNull('companies.deleted_at')
                    ->where('companies.type', 'External')
                    ->where('companies.category', 'End User');
            })
//            ->dd()
            ->select([
                'customers.id',
                'companies.id as company_reference_id',
            ])
            ->get();

        if ($customers->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($customers) {

            foreach ($customers as $customer) {

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['company_reference_id' => $customer->company_reference_id]);

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
