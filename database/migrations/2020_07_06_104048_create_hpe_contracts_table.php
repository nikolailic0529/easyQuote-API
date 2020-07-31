<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hpe_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignUuid('company_id')->nullable()->constrained('companies')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignUuid('country_id')->nullable()->constrained('countries')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignUuid('quote_template_id')->nullable()->constrained('quote_templates')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignUuid('hpe_contract_file_id')->nullable()->constrained('hpe_contract_files')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedTinyInteger('completeness')->default(0)->index()->comment('Contract state completeness');

            $table->json('checkbox_status')->nullable()->comment('Checkbox status for UI purpose');

            $table->date('contract_date')->nullable()->comment('Contract opened date');

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

        Schema::dropIfExists('hpe_contracts');

        Schema::enableForeignKeyConstraints();
    }
}
