<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('contact_languages', static function (Blueprint $table): void {
            $table->foreignUuid('language_id')
                ->comment('Foreign key to languages table')
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->primary('language_id');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('contact_languages');

        Schema::enableForeignKeyConstraints();
    }
};
