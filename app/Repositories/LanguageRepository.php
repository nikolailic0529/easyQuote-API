<?php namespace App\Repositories;

use App\Models\Data\Language;
use App\Contracts\Repositories\LanguageRepositoryInterface;

class LanguageRepository implements LanguageRepositoryInterface
{
    protected $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function all()
    {
        return $this->language->ordered()->get();
    }
}
