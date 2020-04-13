<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteNoteRepositoryInterface as QuoteNotes;
use App\Events\QuoteNoteCreated;
use App\Http\Requests\{
    QuoteNote\CreateQuoteNoteRequest,
    QuoteNote\UpdateQuoteNoteRequest,
};
use App\Http\Resources\{
    Note\QuoteNoteCollection,
    Note\QuoteNoteResource,
};
use App\Models\Quote\{
    Quote,
    QuoteNote,
};
use Illuminate\Http\{
    Request,
    Response,
};

class QuoteNoteController extends Controller
{
    protected QuoteNotes $quoteNotes;

    public function __construct(QuoteNotes $quoteNotes)
    {
        $this->quoteNotes = $quoteNotes;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $resource = $this->quoteNotes->paginate(['quote_id' => $quote->id], $request->query('search'));

        return response()->json(
            QuoteNoteCollection::make($resource)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateQuoteNoteRequest  $request
     * @param  Quote $quote
     * @return \Illuminate\Http\Response
     *
     */
    public function store(CreateQuoteNoteRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        $quoteNote = tap(
            $this->quoteNotes->create($request->validated()),
            fn (QuoteNote $quoteNote) => event(new QuoteNoteCreated($quoteNote))
        );

        return response()->json(QuoteNoteResource::make($quoteNote), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Quote $quote
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return \Illuminate\Http\Response
     */
    public function show(Quote $quote, QuoteNote $quoteNote)
    {
        $this->authorize('view', $quote);

        return response()->json(QuoteNoteResource::make($quoteNote));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateQuoteNoteRequest  $request
     * @param  Quote $quote
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return \Illuminate\Http\Response
     *
     */
    public function update(UpdateQuoteNoteRequest $request, Quote $quote, QuoteNote $quoteNote)
    {
        $this->authorize('update', $quote);
        $this->authorize('update', $quoteNote);

        $resource = $this->quoteNotes->update($quoteNote->id, $request->validated());

        return response()->json(QuoteNoteResource::make($resource));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Quote $quote
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return \Illuminate\Http\Response
     *
     */
    public function destroy(Quote $quote, QuoteNote $quoteNote)
    {
        $this->authorize('update', $quote);
        $this->authorize('delete', $quoteNote);

        return response()->json(
            $this->quoteNotes->delete($quoteNote->id)
        );
    }
}
