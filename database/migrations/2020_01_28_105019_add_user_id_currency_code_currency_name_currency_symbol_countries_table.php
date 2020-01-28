<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{
    Schema,
    DB
};

class AddUserIdCurrencyCodeCurrencyNameCurrencySymbolCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->boolean('is_system')->default(false);
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('currency_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('activated_at')->nullable();
        });

        DB::transaction(function () {
            $created_at = $updated_at = $activated_at = now();

            DB::table('countries')->update(['is_system' => true] + compact('created_at', 'updated_at', 'activated_at'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropTimestamps();
            $table->dropSoftDeletes();
            $table->dropColumn([
                'is_system',
                'user_id',
                'currency_name',
                'currency_symbol',
                'currency_code',
                'activated_at'
            ]);
        });
    }
}
