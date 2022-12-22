<?php

namespace Database\Factories;

use App\Enum\CompanyType;
use App\Enum\DataAllocationStageEnum;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\DataAllocation\DataAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataAllocationFactory extends Factory
{
    protected $model = DataAllocation::class;

    public function definition(): array
    {
        return [
//            'company_id' => Company::query()->where('type', CompanyType::INTERNAL)->get()->random(),
//            'business_division_id' => BusinessDivision::query()->get()->random(),
//            'assignment_start_date' => $this->faker->dateTimeBetween('now', '+3days')->format('Y-m-d'),
            'stage' => DataAllocationStageEnum::Init,
        ];
    }
}

