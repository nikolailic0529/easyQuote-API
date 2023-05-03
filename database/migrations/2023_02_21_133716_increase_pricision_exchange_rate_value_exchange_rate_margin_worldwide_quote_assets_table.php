<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('worldwide_quote_assets', static function (Blueprint $table): void {
            $table->decimal('exchange_rate_value', total: 16, places: 8)->change();
            $table->decimal('exchange_rate_margin', total: 16, places: 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('worldwide_quote_assets', static function (Blueprint $table): void {
            $table->decimal('exchange_rate_value', total: 12, places: 4)->change();
            $table->decimal('exchange_rate_margin', total: 12, places: 4)->change();
        });
    }
};
