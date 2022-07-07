<?php

namespace App\Http\Requests\Pipeliner;

use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Task\Task;
use App\Models\User;
use App\Rules\ModelExists;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncModel extends FormRequest
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
     *
     * @return array
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

        return (new $modelClass)->query()->findOrFail($this->input('model.id'));
    }
}
