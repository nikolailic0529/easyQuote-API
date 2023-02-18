<?php

use Illuminate\Database\Migrations\Migration;

class SetupCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Creates the users table
        Schema::create(\Config::get('countries.table_name'), function ($table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->string('capital', 255)->nullable();
            $table->string('citizenship', 255)->nullable();
            $table->string('country_code', 3)->default('');
            $table->string('full_name', 255)->nullable();
            $table->string('iso_3166_2', 2)->default('');
            $table->string('iso_3166_3', 3)->default('');
            $table->string('name', 255)->default('');
            $table->string('calling_code', 3)->nullable();

            $table->string('currency_name')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('flag')->nullable();

            $table->boolean('is_system')->default(false);

            $table->timestamps();
            $table->timestamp('activated_at')->index()->nullable();
            $table->softDeletes();

            $table->index(['iso_3166_2', 'iso_3166_3']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(\Config::get('countries.table_name'));
    }
}
