<?php

namespace App\Repositories;

use App\Models\Language;
use App\Contracts\Repositories\LanguageRepositoryInterface;

class LanguageRepository implements LanguageRepositoryInterface
{
    public function all()
    {
        return Language::ordered()->get();
    }
}