<?php

namespace App\Http\Requests\Pipeline;

use App\Models\Pipeline\Pipeline;
use App\Queries\PipelineQueries;
use Illuminate\Foundation\Http\FormRequest;

class ShowDefaultPipeline extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getDefaultPipeline(): Pipeline
    {
        /** @var PipelineQueries $queries */
        $queries = $this->container[PipelineQueries::class];

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $queries->explicitlyDefaultPipelinesQuery()->sole();
    }
}
