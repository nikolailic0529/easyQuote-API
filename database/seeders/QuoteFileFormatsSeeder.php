<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webpatser\Uuid\Uuid;

class QuoteFileFormatsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        // Empty the quote_file_formats table
        Schema::disableForeignKeyConstraints();

        DB::table('quote_file_formats')->delete();

        Schema::enableForeignKeyConstraints();

        $fileFormats = json_decode(file_get_contents(__DIR__.'/models/quote_file_formats.json'), true);

        collect($fileFormats)->each(function ($format) {
            DB::table('quote_file_formats')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $format['name'],
                'extension' => $format['extension'],
            ]);
        });
    }
}
