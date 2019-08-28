<?php

use Illuminate\Database\Migrations\Migration;

class SetupCountriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return  void
	 */
	public function up()
	{
		// Creates the users table
		Schema::create(\Config::get('countries.table_name'), function($table)
		{
		    $table->uuid('id')->index();
		    $table->primary('id');
		    $table->string('capital', 255)->nullable();
		    $table->string('citizenship', 255)->nullable();
		    $table->string('country_code', 3)->default('');
		    $table->string('full_name', 255)->nullable();
		    $table->string('iso_3166_2', 2)->default('');
		    $table->string('iso_3166_3', 3)->default('');
		    $table->string('name', 255)->default('');
		    $table->string('calling_code', 3)->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return  void
	 */
	public function down()
	{
		Schema::drop(\Config::get('countries.table_name'));
	}

}
