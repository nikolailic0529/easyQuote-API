<?php

namespace App\Rules\Concerns;
use Illuminate\Database\Eloquent\Model;

trait IgnoresModel
{
    /**
     * The ID that should be ignored.
     *
     * @var mixed
     */
    protected $ignore;

    /**
     * The name of the ID column.
     *
     * @var string
     */
    protected $idColumn = 'id';

    /**
     * Ignore the given ID during the unique check.
     *
     * @param  mixed  $id
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = null)
    {
        if ($id instanceof Model) {
            return $this->ignoreModel($id, $idColumn);
        }

        $this->ignore = $id;
        $this->idColumn = $idColumn ?? 'id';

        return $this;
    }

    /**
     * Ignore the given model during the unique check.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignoreModel($model, $idColumn = null)
    {
        $this->idColumn = $idColumn ?? $model->getKeyName();
        $this->ignore = $model->{$this->idColumn};

        return $this;
    }
}
