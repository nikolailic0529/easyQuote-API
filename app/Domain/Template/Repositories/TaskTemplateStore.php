<?php

namespace App\Domain\Template\Repositories;

use Illuminate\Contracts\Support\Jsonable;
use Spatie\Valuestore\Valuestore;

abstract class TaskTemplateStore extends ValueStore implements Jsonable, \JsonSerializable
{
    /**
     * Set template content.
     *
     * @return $this
     */
    public function setContent(array $values): static
    {
        return parent::setContent($values);
    }

    /**
     * Filepath to default store content.
     */
    abstract public function defaultContentPath(): string;

    /**
     * Reset default values content.
     *
     * @return $this
     */
    public function reset()
    {
        $values = json_decode(file_get_contents($this->defaultContentPath()), true);

        return parent::setContent($values);
    }

    /**
     * Convert the store instance to JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    public function getFileName()
    {
        return $this->fileName;
    }
}
