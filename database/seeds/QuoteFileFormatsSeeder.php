<?php

use Illuminate\Database\Seeder;

class QuoteFileFormatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the quote_file_formats table
        Schema::disableForeignKeyConstraints();
        
        DB::table('quote_file_formats')->delete();

        Schema::enableForeignKeyConstraints();

        $fileFormats = json_decode(file_get_contents(__DIR__ . '/models/quote_file_formats.json'), true);

        collect($fileFormats)->each(function ($format) {
            DB::table('quote_file_formats')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $format['name'],
                'extension' => $format['extension'],
            ]);
        });
    }
}
