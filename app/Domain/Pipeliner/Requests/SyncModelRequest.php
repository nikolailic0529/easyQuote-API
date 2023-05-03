<?php

namespace App\Domain\Pipeliner\Requests;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Company\Models\Company;
use App\Domain\Note\Models\Note;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Foundation\Validation\Rules\ModelExists;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncModelRequest extends FormRequest
{
    protected static array $classMap = [
        'Opportunity' => Opportunity::class,
        'Company' => Company::class,
        'Note' => Note::class,
        'Appointment' => Appointment::class,
        'Task' => Task::class,
        'User' => User::class,
    ];

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'model' => ['bail', 'required', 'array', new ModelExists(static::$classMap)],
            'model.id' => ['bail', 'required', 'string'],
            'model.type' => ['bail', 'required', 'string', Rule::in(array_keys(static::$classMap))],
        ];
    }

    public function getModel(): Model
    {
        $modelClass = static::$classMap[$this->input('model.type')];

        return (new $modelClass())->query()->findOrFail($this->input('model.id'));
    }
}
