<?php namespace App\Contracts\Repositories\Quote\Margin;

interface MarginRepositoryInterface
{
    /**
     * Get data for Creating New Quote
     *
     * @return array
     */
    public function data(): array;
}
