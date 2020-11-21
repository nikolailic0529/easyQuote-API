<?php

use App\Models\Company;
use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdHpeContractTemplateIdUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->after('country_id')->comment('Foreign Key on Companies Table')
                ->constrained('companies')->onDelete('SET NULL')->onUpdate('cascade');

            $table->foreignUuid('hpe_contract_template_id')->nullable()->after('company_id')->comment('Foreign Key on Quote Templates Table')
                ->constrained('quote_templates')->onDelete('SET NULL')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['hpe_contract_template_id']);

            $table->dropColumn(['company_id', 'hpe_contract_template_id']);
        });
    }
}
