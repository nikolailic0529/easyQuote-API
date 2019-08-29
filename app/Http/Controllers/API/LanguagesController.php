<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\LanguageRepositoryInterface;

class LanguagesController extends Controller
{
    protected $language;

    public function __construct(LanguageRepositoryInterface $language)
    {
        $this->language = $language;
    }

    public function __invoke()
    {
        $languages = $this->language->all();
        return response()->json($languages);
    }
}

