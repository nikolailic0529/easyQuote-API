<?php

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Type::addType('char', StringType::class);

        Schema::table('languages', static function (Blueprint $table): void {
            $table->char('code', 2)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('languages', static function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->string('code')->change();
        });
    }
};
