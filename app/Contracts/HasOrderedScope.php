<?php

namespace App\Contracts;

interface HasOrderedScope
{
    /**
     * default model sorting
     * @return Illuminate\Database\Query instance
     */
    public function scopeOrdered($query);
}