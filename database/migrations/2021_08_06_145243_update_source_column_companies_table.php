<?php

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSourceColumnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(<<<SQL
ALTER TABLE companies MODIFY COLUMN source VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'Customer Source';
SQL
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Type::hasType('char')) {
            Type::addType('char', StringType::class);
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->char('source', 3)->change();
        });
    }
}
