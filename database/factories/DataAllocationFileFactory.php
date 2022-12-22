<?php

namespace Database\Factories;

use App\Models\DataAllocation\DataAllocationFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DataAllocationFileFactory extends Factory
{
    protected $model = DataAllocationFile::class;

    public function definition(): array
    {
        return [
            'filename' => 'data-allocation-file.xlsx',
            'filepath' => Str::random(40),
            'extension' => 'csv',
            'size' => 0,
        ];
    }
}

