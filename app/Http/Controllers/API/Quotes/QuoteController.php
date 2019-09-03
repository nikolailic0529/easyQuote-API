<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteRepositoryInterface as QuoteRepository;

class QuoteController extends Controller
{
    protected $quoteRepository;

    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function step1()
    {
        $stepData = $this->quoteRepository->step1();

        return response()->json(
            $stepData
        );
    }
}