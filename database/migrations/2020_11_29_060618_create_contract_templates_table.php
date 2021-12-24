<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_templates', function (Blueprint $table) {
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

        Schema::create('country_contract_template', function (Blueprint $table) {
            $table->foreignUuid('country_id')->comment('Foreign key on countries table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contract_template_id')->comment('Foreign key on contract_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['country_id', 'contract_template_id'], 'country_contract_template_primary');
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

        Schema::dropIfExists('country_contract_template');
        Schema::dropIfExists('contract_templates');

        Schema::enableForeignKeyConstraints();
    }
}
