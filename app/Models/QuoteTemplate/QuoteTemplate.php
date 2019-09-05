<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\TemplateField
};
use App\Traits \ {
    BelongsToUser
};

class QuoteTemplate extends UuidModel
{
    use BelongsToUser;

    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class);
    }
}
