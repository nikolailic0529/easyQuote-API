<?php

use Database\Seeders\CompanyCategorySeeder;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call(SeedCommand::class, [
            '--class' => CompanyCategorySeeder::class,
            '--force' => true,
        ]);
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
};
