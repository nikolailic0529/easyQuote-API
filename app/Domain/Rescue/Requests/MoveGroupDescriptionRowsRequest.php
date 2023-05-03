<?php

namespace App\Domain\Rescue\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class MoveGroupDescriptionRowsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from_group_id' => 'required|string|uuid',
            'to_group_id' => 'required|string|uuid',
            'rows' => 'required|array',
            'rows.*' => 'required|string|uuid|exists:imported_rows,id',
        ];
    }

    public function groupDescription(): Collection
    {
        return collect(($this->route('quote')->activeVersion ?? $this->route('quote'))->group_description);
    }

    public function fromGroupName()
    {
        $group = $this->groupDescription()->firstWhere('id', $this->from_group_id);

        return data_get($group, 'name');
    }

    public function toGroupName()
    {
        $group = $this->groupDescription()->firstWhere('id', $this->to_group_id);

        return data_get($group, 'name');
    }

    protected function passedValidation()
    {
        request()->merge([
            'from_group_name' => $this->fromGroupName(),
            'to_group_name' => $this->toGroupName(),
        ]);
    }
}
