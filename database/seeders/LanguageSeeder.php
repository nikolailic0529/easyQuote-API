<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $seeds = yaml_parse_file(__DIR__.'/models/languages.yaml');
        $contactLanguageSeeds = yaml_parse_file(__DIR__.'/models/contact_languages.yaml');

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

        $contactLanguageSeeds = collect($contactLanguageSeeds)
            ->map(static function (string $code): array {
                $lang = DB::table('languages')
                    ->where('code', $code)
                    ->first();

                if (!$lang) {
                    throw new \LogicException("Could not find language [$code].");
                }

                return ['language_id' => $lang->id];
            })
            ->all();

        DB::transaction(static function () use ($contactLanguageSeeds): void {
            DB::table('contact_languages')->delete();
            DB::table('contact_languages')->insert($contactLanguageSeeds);
        });
    }
}
