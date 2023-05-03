<?php

use App\Domain\Country\Models\Country;
use App\Domain\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCountryIdUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $columns = collect(array_keys($schemaManager->listTableColumns('users')));
        $foreigns = collect($schemaManager->listTableForeignKeys('users'))->map->getName();

        if (!$columns->contains('country_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('country_id')->nullable()->after('timezone_id')->comment('User Country');
            });
        }

        if (!$foreigns->contains('users_country_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('country_id')->references('id')->on('countries')->onDelete('SET NULL')->onUpdate('cascade');
            });
        }

        $country = Country::query()->where('iso_3166_2', 'GB')->first();

        if (null !== $country) {
            DB::transaction(fn () => User::query()->update(['country_id' => $country->getKey()]));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
    }
}
