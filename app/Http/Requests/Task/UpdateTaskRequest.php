<?php

namespace App\Http\Requests\Task;

use App\DTO\Tasks\UpdateTaskData;
use App\Models\{Attachment, User};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    protected ?UpdateTaskData $updateTaskData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'filled', 'max:191'],
            'content' => ['present', 'array'],
            'expiry_date' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'priority' => ['required', 'integer', 'min:1', 'max:3'],
            'users' => ['nullable', 'array'],
            'users.*' => ['present', 'uuid', 'distinct', Rule::exists(User::class, 'id')->whereNull('deleted_at')],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['present', 'uuid', 'distinct', Rule::exists(Attachment::class, 'id')->whereNull('deleted_at')]
        ];
    }

    public function getUpdateTaskData(): UpdateTaskData
    {
        return $this->updateTaskData ??= with(true, function (): UpdateTaskData {
            return new UpdateTaskData([
                'name' => $this->input('name'),
                'content' => $this->input('content'),
                'expiry_date' => transform($this->input('expiry_date'), function (string $expiryDate) {
                    return Carbon::createFromFormat('Y-m-d H:i:s', $expiryDate, auth()->user()->tz)->tz(config('app.timezone'));
                }),
                'priority' => (int)$this->input('priority'),
                'users' => $this->input('users') ?? [],
                'attachments' => $this->input('attachments') ?? [],
            ]);
        });
    }
}
