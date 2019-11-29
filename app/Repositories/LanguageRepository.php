<?php

namespace App\Repositories;

use App\Contracts\Repositories\LanguageRepositoryInterface;
use App\Models\Data\Language;

class LanguageRepository implements LanguageRepositoryInterface
{
    protected $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function all()
    {
        return cache()->sear('all-languages', function () {
            return $this->language->ordered()->get();
        });
    }
}
