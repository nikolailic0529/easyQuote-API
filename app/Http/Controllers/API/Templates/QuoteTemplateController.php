<?php

namespace App\Http\Controllers\API\Templates;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use App\Models\QuoteTemplate\QuoteTemplate;

class QuoteTemplateController extends Controller
{
    protected $quoteTemplate;

    public function __construct(QuoteTemplateRepository $quoteTemplate)
    {
        $this->quoteTemplate = $quoteTemplate;
        $this->authorizeResource(QuoteTemplate::class, 'template');
    }

    /**
     * Display a listing of the User's Quote Templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            request()->filled('search')
                ? $this->quoteTemplate->search(request('search'))
                : $this->quoteTemplate->all()
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
     * @param  QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function show(QuoteTemplate $template)
    {
        return response()->json(
            $this->quoteTemplate->find($template->id)
        );
    }

    /**
     * Update the specified Quote Template in storage.
     *
     * @param  UpdateQuoteTemplateRequest  $request
     * @param  QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQuoteTemplateRequest $request, QuoteTemplate $template)
    {
        return response()->json(
            $this->quoteTemplate->update($request, $template->id)
        );
    }

    /**
     * Remove the specified Quote Template from storage.
     *
     * @param  QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(QuoteTemplate $template)
    {
        return response()->json(
            $this->quoteTemplate->delete($template->id)
        );
    }

    /**
     * Activate the specified Quote Template from storage.
     *
     * @param  QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function activate(QuoteTemplate $template)
    {
        $this->authorize('update', $template);

        return response()->json(
            $this->quoteTemplate->activate($template->id)
        );
    }

    /**
     * Deactivate the specified Quote Template from storage.
     *
     * @param  QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function deactivate(QuoteTemplate $template)
    {
        $this->authorize('update', $template);

        return response()->json(
            $this->quoteTemplate->deactivate($template->id)
        );
    }

    /**
     * Find the specified Quote Templates by Country.
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function country(string $id)
    {
        $this->authorize('viewAny', QuoteTemplate::class);

        return response()->json(
            $this->quoteTemplate->country($id)
        );
    }

    /**
     * Get Data for Template Designer.
     *
     * @param QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function designer(QuoteTemplate $template)
    {
        $this->authorize('view', $template);

        return response()->json(
            $this->quoteTemplate->designer($template->id)
        );
    }

    /**
     * Create copy of the specified Quote Template.
     *
     * @param QuoteTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function copy(QuoteTemplate $template)
    {
        $this->authorize('copy', $template);

        return response()->json(
            $this->quoteTemplate->copy($template->id)
        );
    }
}
