<?php

use App\Domain\Company\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShortCodeCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->char('short_code', 3)->nullable()->index()->after('name')->comment('Company short name');
        });

        $seeds = collect(yaml_parse_file(database_path('seeders/models/companies.yaml')));

        DB::transaction(fn () => $seeds->each(
            fn ($attributes) => Company::whereName($attributes['name'])->update(['short_code' => $attributes['short_code']])
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['short_code']);
            $table->dropColumn('short_code');
        });
    }
}
