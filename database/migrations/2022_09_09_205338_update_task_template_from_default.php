<?php

use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $path = storage_path('valuestore/quote.task.template.json');

        if (!file_exists($path)) {
            return;
        }

        copy(
            $path,
            storage_path(sprintf('valuestore/quote.task.template.json.bak.%s', now()->format('YmdHis')))
        );

        copy(
            storage_path('_valuestore/quote.task.template.json'),
            $path
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
