<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('contact_languages', static function (Blueprint $table): void {
            $table->unsignedBigInteger('entity_order')->default(0)->comment('The entity order column');
        });
    }

    public function down(): void
    {
        Schema::table('contact_languages', static function (Blueprint $table): void {
            $table->dropColumn('entity_order');
        });
    }
};
