<?php

namespace Database\Factories;

use App\Domain\Note\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'note' => $this->faker->paragraph(),
        ];
    }
}
