<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class() extends Migration {
    public function up(): void
    {
        $seeds = yaml_parse_file(database_path('seeders/models/languages.yaml'));

        $seeds = collect($seeds)
            ->lazy()
            ->map(static function (array $seed): array {
                return [
                    'id' => Str::orderedUuid()->toString(),
                    'code' => $seed['code'],
                    'name' => $seed['name'],
                    'native_name' => $seed['nativeName'],
                ];
            })
            ->collect();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('languages')
                    ->upsert($seed, 'code', [
                        'name' => $seed['name'],
                        'native_name' => $seed['native_name'],
                    ]);
            }
        });

        $lang = DB::table('languages')
            ->where('code', 'en')
            ->first();

        if (!$lang) {
            throw new LogicException('Could not find language [en].');
        }

        DB::table('contacts')
            ->whereNull('language_id')
            ->update(['language_id' => $lang->id]);
    }

    public function down(): void
    {
    }
};
