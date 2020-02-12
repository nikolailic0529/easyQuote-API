<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumns;
use App\Http\Resources\ImportableColumn\ImportableColumnCollection;
use App\Http\Resources\ImportableColumn\ImportableColumnResource;
use App\Models\QuoteFile\ImportableColumn;

class ImportableColumnController extends Controller
{
    /** @var \App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface */
    protected $importableColumns;

    public function __construct(ImportableColumns $importableColumns)
    {
        $this->importableColumns = $importableColumns;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            ImportableColumnCollection::make($this->importableColumns->paginate())
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return \Illuminate\Http\Response
     */
    public function show(ImportableColumn $importableColumn)
    {
        return response()->json(
            filter(ImportableColumnResource::make($importableColumn))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
