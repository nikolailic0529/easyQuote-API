<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateOpportunityTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // backup of the existing template schema
        $originalValueStoreData = file_get_contents(storage_path('valuestore/opportunity.template.json'));

        file_put_contents(storage_path('valuestore/opportunity-backup-10042021.template.json'), $originalValueStoreData);

        $newValueStoreData = file_get_contents(storage_path('_valuestore/opportunity.template.json'));

        file_put_contents(storage_path('valuestore/opportunity.template.json'), $newValueStoreData);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // backup of the previous template schema
        $backupFilePath = storage_path('valuestore/opportunity-backup-10042021.template.json');

        if (false === file_exists($backupFilePath)) {
            return;
        }

        $originalValueStoreData = file_get_contents($backupFilePath);

        file_put_contents(storage_path('valuestore/opportunity.template.json'), $originalValueStoreData);

        unlink($backupFilePath);
    }
}
