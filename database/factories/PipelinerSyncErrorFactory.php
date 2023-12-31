<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelinerSyncErrorFactory extends Factory
{
    protected $model = PipelinerSyncError::class;

    public function definition(): array
    {
        return [
            'entity_id' => Opportunity::factory()->for(Company::factory(), 'primaryAccount'),
            'entity_type' => (new Opportunity())->getMorphClass(),
            'error_message' => function (array $attributes) {
                /** @var \App\Domain\Worldwide\Models\Opportunity $model */
                $model = Opportunity::find($attributes['entity_id']);

                return "Unable push Opportunity [$model->project_name] to pipeliner due to errors. Company [{$model->primaryAccount->name}] must have default invoice address.";
            },
        ];
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }
}
