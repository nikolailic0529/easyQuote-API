<?php

namespace Database\Factories;

use App\Domain\Worldwide\Models\OpportunityValidationResult;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\MessageBag;

class OpportunityValidationResultFactory extends Factory
{
    protected $model = OpportunityValidationResult::class;

    public function definition(): array
    {
        return [
            'is_passed' => true,
            'messages' => (new MessageBag())->add('one', $this->faker->text)->add('two', $this->faker->text),
        ];
    }
}
