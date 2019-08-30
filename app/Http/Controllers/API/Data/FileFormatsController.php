<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;

class FileFormatsController extends Controller
{
    protected $fileFormat;

    public function __construct(FileFormatRepositoryInterface $fileFormat)
    {
        $this->fileFormat = $fileFormat;
    }

    public function __invoke()
    {
        $fileFormats = $this->fileFormat->all();
        return response()->json($fileFormats);
    }
}
