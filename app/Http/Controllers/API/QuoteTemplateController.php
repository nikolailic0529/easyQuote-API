<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository;
use App\Http\Requests\QuoteTemplate \ {
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};

class QuoteTemplateController extends Controller
{
    protected $quoteTemplate;

    public function __construct(QuoteTemplateRepository $quoteTemplate)
    {
        $this->quoteTemplate = $quoteTemplate;
    }

    /**
     * Display a listing of the User's Quote Templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->quoteTemplate->search(request('search'))
            );
        }

        return response()->json(
            $this->quoteTemplate->all()
        );
    }

    /**
     * Store a newly created Quote Template in storage.
     *
     * @param  StoreQuoteTemplateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQuoteTemplateRequest $request)
    {
        return response()->json(
            $this->quoteTemplate->create($request)
        );
    }

    /**
     * Display the specified Quote Template.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->quoteTemplate->find($id)
        );
    }

    /**
     * Update the specified Quote Template in storage.
     *
     * @param  UpdateQuoteTemplateRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQuoteTemplateRequest $request, string $id)
    {
        return response()->json(
            $this->quoteTemplate->update($request, $id)
        );
    }

    /**
     * Remove the specified Quote Template from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->quoteTemplate->delete($id)
        );
    }

    /**
     * Activate the specified Quote Template from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->quoteTemplate->activate($id)
        );
    }

    /**
     * Deactivate the specified Quote Template from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->quoteTemplate->deactivate($id)
        );
    }

    /**
     * Find the specified Quote Templates by Country
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function country(string $id)
    {
        return response()->json(
            $this->quoteTemplate->country($id)
        );
    }
}
