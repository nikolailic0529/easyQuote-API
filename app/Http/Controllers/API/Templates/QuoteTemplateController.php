<?php

namespace App\Http\Controllers\API\Templates;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use App\Http\Resources\TemplateRepository\{
    TemplateCollection,
    TemplateResourceListing,
    TemplateResourceWithIncludes
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
     * Display a listing of the Quote Templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->quoteTemplate->search(request('search'))
            : $this->quoteTemplate->all();

        return response()->json(
            TemplateCollection::make($resource)
        );
    }

    /**
     * Display a listing of the Quote Templates by specified Country.
     *
     * @param  string $country
     * @return \Illuminate\Http\Response
     */
    public function country(string $country)
    {
        $this->authorize('viewAny', QuoteTemplate::class);

        $resource = $this->quoteTemplate->country($country);

        return response()->json(TemplateResourceListing::collection($resource));
    }

    /**
     * Store a newly created Quote Template in storage.
     *
     * @param  StoreQuoteTemplateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQuoteTemplateRequest $request)
    {
        $template = $this->quoteTemplate->create($request);

        return response()->json(
            TemplateResourceWithIncludes::make($template)
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
        return response()->json(TemplateResourceWithIncludes::make($template));
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
        $template = $this->quoteTemplate->update($request, $template->id);

        return response()->json(TemplateResourceWithIncludes::make($template));
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
