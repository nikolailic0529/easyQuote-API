<?php

namespace App\Http\Requests\Task;

use App\Models\{
    Attachment,
    User
};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class CreateTaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'          => ['required', 'string', 'filled', 'max:191'],
            'content'       => ['present', 'array'],
            'expiry_date'   => ['nullable', 'date_format:Y-m-d H:i:s'],
            'priority'      => ['required', 'integer', 'min:1', 'max:3'],
            'users'         => ['nullable', 'array'],
            'users.*'       => ['present', 'uuid', 'distinct', Rule::exists(User::class, 'id')->whereNull('deleted_at')],
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['present', 'uuid', 'distinct', Rule::exists(Attachment::class, 'id')->whereNull('deleted_at')]
        ];
    }

    public function validated()
    {
        $expiry_date = Carbon::createFromFormat('Y-m-d H:i:s', $this->input('expiry_date'), auth()->user()->tz)
            ->tz(config('app.timezone'));

        return compact('expiry_date') + parent::validated();
    }
}
