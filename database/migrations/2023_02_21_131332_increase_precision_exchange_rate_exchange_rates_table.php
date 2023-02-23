<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('exchange_rates', static function (Blueprint $table): void {
            $table->decimal('exchange_rate', total: 16, places: 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', static function (Blueprint $table): void {
            $table->decimal('exchange_rate', total: 8, places: 4)->change();
        });
    }
};
