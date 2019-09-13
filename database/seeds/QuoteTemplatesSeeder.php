<?php

use Illuminate\Database\Seeder;

class QuoteTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the quote_templates table
        Schema::disableForeignKeyConstraints();

        DB::table('quote_templates')->delete();

        Schema::enableForeignKeyConstraints();

        $templates = json_decode(file_get_contents(__DIR__ . '/models/quote_templates.json'), true);

        collect($templates)->each(function ($template) {
            DB::table('quote_templates')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $template['name']
            ]);
        });
    }
}
