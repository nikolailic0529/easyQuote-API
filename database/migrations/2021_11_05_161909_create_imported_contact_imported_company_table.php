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
        Schema::create('imported_contact_imported_company', function (Blueprint $table) {
            $table->foreignUuid('imported_company_id')->comment('Foreign key on imported_companies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('imported_contact_id')->comment('Foreign key on imported_contacts table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['imported_company_id', 'imported_contact_id'], 'imported_contact_imported_company_primary');
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

        Schema::dropIfExists('imported_contact_imported_company');

        Schema::enableForeignKeyConstraints();
    }
};
