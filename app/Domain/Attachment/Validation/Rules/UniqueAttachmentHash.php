<?php

namespace App\Domain\Attachment\Validation\Rules;

use App\Domain\Attachment\Contracts\AttachmentHasher;
use App\Domain\Attachment\Models\Attachable;
use App\Domain\Attachment\Models\Attachment;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class UniqueAttachmentHash implements Rule
{
    public function __construct(
        protected readonly AttachmentHasher $hasher,
        protected ?Model $forModel = null,
    ) {
    }

    public function for(Model $model): static
    {
        $this->forModel = $model;
        return $this;
    }

    /**
     * @param string $attribute
     */
    public function passes($attribute, mixed $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        $model = new Attachment();
        $pivotModel = new Attachable();

        $query = $model->newQuery()
            ->join(
                $pivotModel->getTable(),
                $model->attachables()->getQualifiedParentKeyName(),
                $pivotModel->getQualifiedKeyName()
            )
            ->where($model->qualifyColumn('md5_hash'), $this->hasher->hash($value->getRealPath()));

        if ($this->forModel) {
            $query->where($pivotModel->related()->getQualifiedForeignKeyName(), $this->forModel->getKey());
        }

        return $query->doesntExist();
    }

    public function message(): string
    {
        return __('An attachment with the same content already exists.');
    }
}
