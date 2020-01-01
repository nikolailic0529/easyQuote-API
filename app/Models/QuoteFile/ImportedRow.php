<?php

namespace App\Models\QuoteFile;

use App\Models\BaseModel;
use App\Traits\{
    BelongsToUser,
    BelongsToQuoteFile,
    HasColumnsData,
    Draftable,
    Selectable,
    Import\Proccessable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportedRow extends BaseModel
{
    use Proccessable, HasColumnsData, BelongsToUser, BelongsToQuoteFile, Draftable, Selectable, SoftDeletes;

    protected $fillable = [
        'page', 'quote_file_id', 'user_id'
    ];

    protected $hidden = [
        'quote_file', 'user', 'quote_file_id', 'user_id', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'page',
        'laravel_through_key'
    ];

    protected $casts = [
        'is_selected' => 'boolean'
    ];

    protected $attributes = [
        'is_selected' => false
    ];

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        if (!isset($this->columnsDataToCreate)) {
            return parent::save($options);
        }

        $columnsDataToCreate = $this->columnsDataToCreate;
        unset($this->columnsDataToCreate);

        $save = parent::save($options);
        $this->columnsData()->saveMany($columnsDataToCreate);
        $this->markAsProcessed();

        return $save;
    }
}
