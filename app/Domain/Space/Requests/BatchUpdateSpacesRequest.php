<?php

namespace App\Domain\Space\Requests;

use App\Domain\Space\DataTransferObjects\PutSpaceData;
use App\Domain\Space\DataTransferObjects\PutSpaceDataCollection;
use App\Domain\Space\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchUpdateSpacesRequest extends FormRequest
{
    protected ?PutSpaceDataCollection $putSpaceDataCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'spaces' => [
                'present', 'array',
            ],
            'spaces.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                Rule::exists(Space::class, 'id')->whereNull('deleted_at'),
            ],
            'spaces.*.space_name' => [
                'bail', 'required', 'string', 'max:191',
            ],
        ];
    }

    public function getPutSpaceDataCollection(): PutSpaceDataCollection
    {
        return $this->putSpaceDataCollection ??= value(function (): PutSpaceDataCollection {
            $collection = array_map(function (array $spaceData): PutSpaceData {
                return new PutSpaceData([
                    'space_id' => $spaceData['id'],
                    'space_name' => $spaceData['space_name'],
                ]);
            }, $this->input('spaces'));

            return new PutSpaceDataCollection($collection);
        });
    }
}
