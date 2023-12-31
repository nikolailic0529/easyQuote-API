<?php

namespace App\Domain\Activity\Requests;

use App\Domain\Activity\Models\Activity;
use App\Domain\Activity\Services\ActivityDataMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetActivitiesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'types' => [
                'nullable',
                'array',
            ],
            'types.*' => [
                'required',
                'string',
                Rule::in(config('activitylog.types')),
            ],
            'period' => [
                'nullable',
                'string',
                Rule::in(config('activitylog.periods')),
            ],
            'subject_types' => [
                'nullable',
                'array',
            ],
            'subject_types.*' => [
                'required',
                'string',
                Rule::in(array_keys(config('activitylog.subject_types'))),
            ],
            'custom_period' => 'array',
            'custom_period.from' => [
                'required_with:custom_period',
                'string',
                'date_format:Y-m-d',
            ],
            'custom_period.till' => [
                'required_with:custom_period',
                'string',
                'date_format:Y-m-d',
            ],
            'causer_id' => [
                'nullable',
                'string',
                'uuid',
                'exists:users,id',
            ],
            'per_page' => 'nullable|numeric',
            'search' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'types.*.required' => 'An invalid activity type provided.',
            'subject_types.*.required' => 'An invalid subject type provided.',
        ];
    }

    public function getSubjectName(): ?string
    {
        return transform($this->route('subject'), function (string $subjectKey) {
            /** @var Activity|null $activity */
            $activity = Activity::query()->where('subject_id', $subjectKey)->first();

            if (is_null($activity)) {
                return null;
            }

            $dataMapper = $this->container[ActivityDataMapper::class];

            return $dataMapper->resolveSubjectNameOfActivity($activity);
        });
    }
}
