<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateMorphClassOfCompanyEntity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('activity_log')
            ->where('subject_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'subject_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);

        DB::table('addressables')
            ->where('addressable_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'addressable_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);

        DB::table('attachables')
            ->where('attachable_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'attachable_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);

        DB::table('contactables')
            ->where('contactable_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'contactable_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);

        DB::table('images')
            ->where('imageable_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'imageable_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);

        DB::table('model_has_roles')
            ->where('model_type', \App\Domain\Company\Models\Company::class)
            ->update([
                'model_type' => '5b2fe950-aa70-4c36-9b1f-1383daecbb18',
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('activity_log')
            ->where('subject_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'subject_type' => \App\Domain\Company\Models\Company::class,
            ]);

        DB::table('addressables')
            ->where('addressable_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'addressable_type' => \App\Domain\Company\Models\Company::class,
            ]);

        DB::table('attachables')
            ->where('attachable_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'attachable_type' => \App\Domain\Company\Models\Company::class,
            ]);

        DB::table('contactables')
            ->where('contactable_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'contactable_type' => \App\Domain\Company\Models\Company::class,
            ]);

        DB::table('images')
            ->where('imageable_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'imageable_type' => \App\Domain\Company\Models\Company::class,
            ]);

        DB::table('model_has_roles')
            ->where('model_type', '5b2fe950-aa70-4c36-9b1f-1383daecbb18')
            ->update([
                'model_type' => \App\Domain\Company\Models\Company::class,
            ]);
    }
}
