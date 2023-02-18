<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDocumentProcessorDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_processor_drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('driver_name', 250)->comment('Driver name');
        });

        $drivers = [
            [
                'id' => 'b1940067-46ea-44cd-aa65-82a9e29d3068',
                'driver_name' => 'EqCsvRescuePriceListProcessor',
            ],
            [
                'id' => '4e26d29a-7af1-47ce-b08e-888b14b75adf',
                'driver_name' => 'EqExcelPriceListProcessor',
            ],
            [
                'id' => '94102e0c-8cb4-4b0a-9dc4-4a78af90c624',
                'driver_name' => 'EqExcelRescuePaymentScheduleProcessor',
            ],
            [
                'id' => 'b873a78e-d950-438e-b8c6-fb791582cf98',
                'driver_name' => 'EqPdfRescuePriceListProcessor',
            ],
            [
                'id' => '87272c93-348e-4ca8-bd67-f10d422aaf53',
                'driver_name' => 'EqPdfRescuePaymentScheduleProcessor',
            ],
            [
                'id' => '87ece929-0818-430f-be7b-93a1997299a9',
                'driver_name' => 'EqWordRescuePriceListProcessor',
            ],
            [
                'id' => '31b77d6a-7321-42d3-9ba0-0ba1ca9e4c0e',
                'driver_name' => 'DePdfRescuePaymentScheduleProcessor',
            ],
            [
                'id' => '256a550e-74a9-4ff1-a133-40bc645a13f5',
                'driver_name' => 'DePdfRescuePriceListProcessor',
            ],
            [
                'id' => 'a74a4bb6-4451-4c25-8352-da9b17407972',
                'driver_name' => 'DePdfWorldwidePriceListProcessor',
            ],
            [
                'id' => 'f476e7f3-0345-4d3a-8c09-fea421dd8edc',
                'driver_name' => 'DeWordRescuePriceListProcessor',
            ],
        ];

        DB::transaction(function () use ($drivers) {
            foreach ($drivers as $driver) {
                DB::table('document_processor_drivers')
                    ->insertOrIgnore([
                        'id' => $driver['id'],
                        'driver_name' => $driver['driver_name'],
                    ]);
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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('document_processor_drivers');

        Schema::enableForeignKeyConstraints();
    }
}
