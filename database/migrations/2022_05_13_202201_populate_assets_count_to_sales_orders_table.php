<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $orders = DB::table('sales_orders')
            ->select([
                'sales_orders.id',
                'sales_orders.worldwide_quote_id',
                'worldwide_quote_versions.id as active_version_id',
                'worldwide_quote_versions.use_groups',
                'opportunities.id as opportunity_id',
                'opportunities.contract_type_id as contract_type_id',
                'contract_types.type_short_name as contract_type_name',
            ])
            ->join('worldwide_quotes', 'worldwide_quotes.id', 'sales_orders.worldwide_quote_id')
            ->join('worldwide_quote_versions', 'worldwide_quote_versions.id', 'worldwide_quotes.active_version_id')
            ->join('opportunities', 'opportunities.id', 'worldwide_quotes.opportunity_id')
            ->join('contract_types', 'contract_types.id', 'opportunities.contract_type_id')
            ->orderBy('sales_orders.id')
            ->get();

        $seeds = [];

        foreach ($orders as $order) {
            $seeds[] = [
                'id' => $order->id,
                'contract_type_name' => $order->contract_type_name,
                'assets_count' => $this->countAssetsOfOrder($order),
            ];
        }

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('sales_orders')
                    ->where('id', $seed['id'])
                    ->update(['assets_count' => $seed['assets_count']]);
            }
        });
    }

    private function countAssetsOfOrder(object $order): int
    {
        return match ($order->contract_type_name) {
            'Contract' => value(function () use ($order): int {
                $count = 0;

                $distributorQuotes = DB::table('worldwide_distributions')
                    ->where('worldwide_quote_id', $order->active_version_id)
                    ->select(['id', 'use_groups', 'distributor_file_id'])
                    ->get();

                foreach ($distributorQuotes as $quote) {
                    if ($quote->use_groups) {
                        $count += DB::table('distribution_rows_groups')
                            ->where('worldwide_distribution_id', $quote->id)
                            ->where('distribution_rows_groups.is_selected', true)
                            ->join('distribution_rows_group_mapped_row', 'distribution_rows_group_mapped_row.rows_group_id', 'distribution_rows_groups.id')
                            ->count();
                    } else {
                        $count += DB::table('mapped_rows')
                            ->where('mapped_rows.quote_file_id', $quote->distributor_file_id)
                            ->where('mapped_rows.is_selected', true)
                            ->count();
                    }
                }

                return $count;
            }),

            'Pack' => value(function () use ($order): int {
                $count = 0;

                if ($order->use_groups) {
                    $count += DB::table('worldwide_quote_assets_groups')
                        ->where('worldwide_quote_assets_groups.worldwide_quote_version_id', $order->active_version_id)
                        ->join('worldwide_quote_assets_group_asset', 'worldwide_quote_assets_group_asset.group_id', 'worldwide_quote_assets_groups.id')
                        ->count();
                } else {
                    $count += DB::table('worldwide_quote_assets')
                        ->where('worldwide_quote_assets.worldwide_quote_id', $order->active_version_id)
                        ->where('worldwide_quote_assets.is_selected', true)
                        ->count();
                }

                return $count;
            })
        };
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
