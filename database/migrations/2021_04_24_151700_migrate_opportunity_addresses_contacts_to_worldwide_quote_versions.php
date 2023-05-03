<?php

use Illuminate\Database\Migrations\Migration;

class MigrateOpportunityAddressesContactsToWorldwideQuoteVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migrate addresses from opportunity entities
        // to the corresponding versions of worldwide quote entities.
        DB::transaction(function () {
            $addressPivotQuery = DB::table('opportunities')
                ->select('address_opportunity.address_id as address_id', 'worldwide_quote_versions.id as worldwide_quote_version_id')
                ->join('worldwide_quotes', 'worldwide_quotes.opportunity_id', 'opportunities.id')
                ->join('worldwide_quote_versions', 'worldwide_quote_versions.worldwide_quote_id', 'worldwide_quotes.id')
                ->join('address_opportunity', 'address_opportunity.opportunity_id', 'opportunities.id');

            $contactPivotQuery = DB::table('opportunities')
                ->select('contact_opportunity.contact_id as contact_id', 'worldwide_quote_versions.id as worldwide_quote_version_id')
                ->join('worldwide_quotes', 'worldwide_quotes.opportunity_id', 'opportunities.id')
                ->join('worldwide_quote_versions', 'worldwide_quote_versions.worldwide_quote_id', 'worldwide_quotes.id')
                ->join('contact_opportunity', 'contact_opportunity.opportunity_id', 'opportunities.id');

            DB::table('address_worldwide_quote_version')
                ->insertUsing(['address_id', 'worldwide_quote_version_id'], $addressPivotQuery);

            DB::table('contact_worldwide_quote_version')
                ->insertUsing(['contact_id', 'worldwide_quote_version_id'], $contactPivotQuery);
        });
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
