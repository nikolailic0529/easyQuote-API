<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class ModelExists implements Rule
{
    public function __construct(protected array  $classMap,
                                protected string $idField = 'id',
                                protected string $typeField = 'type')
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $id = $value[$this->idField] ?? '';
        $type = $value[$this->typeField] ?? '';
        $modelClass = $this->classMap[$type] ?? '';

        if (false === is_string($modelClass)
            || false === class_exists($modelClass)
            || false === is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        /** @var Model $model */
        $model = new $modelClass;

        return $model->newQuery()
            ->where($model->getQualifiedKeyName(), $id)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "Model doesn't exist in database.";
    }
}
