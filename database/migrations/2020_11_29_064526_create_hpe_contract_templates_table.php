<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHpeContractTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hpe_contract_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('company_id')->comment('Foreign key on companies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->comment('Foreign key on vendors table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('currency_id')->nullable()->comment('Foreign key on currencies table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->string('name')->comment('Template name');
            $table->boolean('is_system')->default(false)->comment('Whether the template is a system template');
            $table->json('form_data')->nullable()->comment('Template json schema');
            $table->json('data_headers')->nullable()->comment('Template json translations');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('activated_at')->nullable()->index();
        });

        Schema::create('country_hpe_contract_template', function (Blueprint $table) {
            $table->foreignUuid('country_id')->comment('Foreign key on countries table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('hpe_contract_template_id')->comment('Foreign key on hpe_contract_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['country_id', 'hpe_contract_template_id'], 'country_hpe_contract_template_primary');
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

        Schema::dropIfExists('hpe_contract_templates');

        Schema::enableForeignKeyConstraints();
    }
}
