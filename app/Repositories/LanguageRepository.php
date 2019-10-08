<?php namespace App\Repositories;

use App\Models\Data\Language;
use App\Contracts\Repositories\LanguageRepositoryInterface;
use Cache;

class LanguageRepository implements LanguageRepositoryInterface
{
    protected $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function all()
    {
        return Cache::rememberForever('all-languages', function () {
            return $this->language->ordered()->get();
        });
    }
}
