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
        Schema::create('imported_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('address_type')->nullable()->comment('Address Type');

            $table->foreignUuid('country_id')->nullable()->comment('Foreign key on countries table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('address_1')->nullable()->comment('Address One');
            $table->string('address_2')->nullable()->comment('Address Two');

            $table->string('city')->nullable()->comment('City');
            $table->string('post_code')->nullable()->comment('Post Code');
            $table->string('state')->nullable()->comment('State');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('imported_addresses');

        Schema::enableForeignKeyConstraints();
    }
};
