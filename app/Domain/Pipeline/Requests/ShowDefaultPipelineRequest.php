<?php

namespace App\Domain\Pipeline\Requests;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Queries\PipelineQueries;
use Illuminate\Foundation\Http\FormRequest;

class ShowDefaultPipelineRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function getDefaultPipeline(): Pipeline
    {
        /** @var \App\Domain\Pipeline\Queries\PipelineQueries $queries */
        $queries = $this->container[PipelineQueries::class];

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $queries->explicitlyDefaultPipelinesQuery()->sole();
    }
}
