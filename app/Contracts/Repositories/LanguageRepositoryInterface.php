<?php

namespace App\Contracts\Repositories;

interface LanguageRepositoryInterface
{
    /**
     * Get all languages
     *
     * @return \Illuminate\Database\Eloquent\Collection 
     */
    public function all();
}