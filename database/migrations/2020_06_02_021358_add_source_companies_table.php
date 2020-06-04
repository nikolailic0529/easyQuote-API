<?php

use App\Models\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSourceCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->char('source', 3)->nullable()->after('category')->index()->comment('Customer Source');

            $table->dropIndex(['type', 'category']);

            $table->string('type')->change();
            $table->string('category')->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            /**
             * type max length is 8 chars
             * category max length is 16 chars
             */
            $table->rawIndex(DB::raw('type(8), category(16)'), 'companies_type_category_index');
        });

        DB::transaction(fn () =>
            Company::whereType('External')->whereCategory('End User')->update(['source' => Customer::S4_SOURCE])
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['type', 'category']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');

            $table->index(['type', 'category']);
        });
    }
}
