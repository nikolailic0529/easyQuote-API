<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        copy(
            storage_path('valuestore/quote.task.template.json'),
            storage_path(sprintf("valuestore/quote.task.template.json.bak.%s", now()->format('YmdHis')))
        );

        copy(
            storage_path('_valuestore/quote.task.template.json'),
            storage_path('valuestore/quote.task.template.json')
        );
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
