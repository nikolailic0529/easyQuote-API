<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumns;
use App\Http\Requests\ImportableColumn\{
    CreateImportableColumnRequest,
    UpdateImportableColumnRequest
};
use App\Http\Resources\ImportableColumn\{
    ImportableColumnCollection,
    ImportableColumnResource
};
use App\Models\QuoteFile\ImportableColumn;

class ImportableColumnController extends Controller
{
    /** @var \App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface */
    protected $importableColumns;

    public function __construct(ImportableColumns $importableColumns)
    {
        $this->importableColumns = $importableColumns;

        $this->authorizeResource(ImportableColumn::class, 'importable_column');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->importableColumns->search(request('search'))
            : $this->importableColumns->paginate();

        return response()->json(
            ImportableColumnCollection::make($resource)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ImportableColumn\CreateImportableColumnRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateImportableColumnRequest $request)
    {
        $importableColumn = $this->importableColumns->create($request->validated());

        return response()->json(
            filter(ImportableColumnResource::make($importableColumn)),
            201
        );
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
     * @param  \App\Http\Requests\ImportableColumn\UpdateImportableColumnRequest  $request
     * @param \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateImportableColumnRequest $request, ImportableColumn $importableColumn)
    {
        $importableColumn = $this->importableColumns->update($request->validated(), $importableColumn->id);

        return response()->json(
            filter(ImportableColumnResource::make($importableColumn))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return \Illuminate\Http\Response
     */
    public function destroy(ImportableColumn $importableColumn)
    {
        return response()->json(
            $this->importableColumns->delete($importableColumn->id)
        );
    }

    /**
     * Activate the specified resource in storage.
     *
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return \Illuminate\Http\Response
     */
    public function activate(ImportableColumn $importableColumn)
    {
        $this->authorize('activate', $importableColumn);

        return response()->json(
            $this->importableColumns->activate($importableColumn->id)
        );
    }

    /**
     * Deactivate the specified resource in storage.
     *
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return \Illuminate\Http\Response
     */
    public function deactivate(ImportableColumn $importableColumn)
    {
        $this->authorize('deactivate', $importableColumn);

        return response()->json(
            $this->importableColumns->deactivate($importableColumn->id)
        );
    }
}
