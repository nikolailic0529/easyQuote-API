<?php

use App\Models\Customer\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\DB;

class UpdateCustomersTable extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'text');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('char')) {
            Type::addType('char', StringType::class);
        }

        DB::transaction(fn () =>
        Customer::query()->whereSource('easyQuote')->update(['source' => Customer::EQ_SOURCE]));

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->char('source', 3)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->string('source')->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('source');
        });
    }
}
