<?php

namespace App\Http\Controllers\API\Templates;

use App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface as Repository;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use App\Http\Resources\TemplateRepository\{
    TemplateCollection,
    TemplateResourceListing,
    TemplateResourceWithIncludes
};
use App\Models\QuoteTemplate\ContractTemplate;

class ContractTemplateController extends Controller
{
    /** @var \App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface */
    protected $contractTemplate;

    public function __construct(Repository $contractTemplate)
    {
        $this->contractTemplate = $contractTemplate;
        $this->authorizeResource(ContractTemplate::class, 'contract_template');
    }

    /**
     * Display a listing of the Contract Templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->contractTemplate->search(request('search'))
            : $this->contractTemplate->paginate();

        return response()->json(TemplateCollection::make($resource));
    }

    /**
     * Display a listing of the Quote Templates by specified Country.
     *
     * @param  string $country
     * @return \Illuminate\Http\Response
     */
    public function country(string $country)
    {
        $this->authorize('viewAny', ContractTemplate::class);

        $resource = $this->contractTemplate->country($country);

        return response()->json(TemplateResourceListing::collection($resource));
    }

    /**
     * Store a newly created Quote Template in storage.
     *
     * @param  \App\Http\Requests\QuoteTemplate\StoreQuoteTemplateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQuoteTemplateRequest $request)
    {
        $template = $this->contractTemplate->create($request->validated());

        return response()->json(TemplateResourceWithIncludes::make($template));
    }

    /**
     * Display the specified Contract Template.
     *
     * @param  \App\Models\QuoteTemplate\ContractTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function show(ContractTemplate $contract_template)
    {
        return response()->json(TemplateResourceWithIncludes::make($contract_template));
    }

    /**
     * Update the specified Contract Template in storage.
     *
     * @param  \App\Http\Requests\QuoteTemplate\UpdateQuoteTemplateRequest  $request
     * @param  \App\Models\QuoteTemplate\ContractTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQuoteTemplateRequest $request, ContractTemplate $contract_template)
    {
        $template = $this->contractTemplate->update($request->validated(), $contract_template->id);

        return response()->json(TemplateResourceWithIncludes::make($template));
    }

    /**
     * Remove the specified Contract Template from storage.
     *
     * @param  \App\Models\QuoteTemplate\ContractTemplate $contract_template
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContractTemplate $contract_template)
    {
        return response()->json(
            $this->contractTemplate->delete($contract_template->id)
        );
    }

    /**
     * Activate the specified Contract Template from storage.
     *
     * @param  \App\Models\QuoteTemplate\ContractTemplate $contract_template
     * @return \Illuminate\Http\Response
     */
    public function activate(ContractTemplate $contract_template)
    {
        $this->authorize('update', $contract_template);

        return response()->json(
            $this->contractTemplate->activate($contract_template->id)
        );
    }

    /**
     * Deactivate the specified Contract Template from storage.
     *
     * @param  \App\Models\QuoteTemplate\ContractTemplate $contract_template
     * @return \Illuminate\Http\Response
     */
    public function deactivate(ContractTemplate $contract_template)
    {
        $this->authorize('update', $contract_template);

        return response()->json(
            $this->contractTemplate->deactivate($contract_template->id)
        );
    }

    /**
     * Get Data for Template Designer.
     *
     * @param \App\Models\QuoteTemplate\ContractTemplate $contract_template
     * @return \Illuminate\Http\Response
     */
    public function designer(ContractTemplate $contract_template)
    {
        $this->authorize('view', $contract_template);

        return response()->json(
            $this->contractTemplate->designer($contract_template->id)
        );
    }

    /**
     * Create copy of the specified Contract Template.
     *
     * @param \App\Models\QuoteTemplate\ContractTemplate $contract_template
     * @return \Illuminate\Http\Response
     */
    public function copy(ContractTemplate $contract_template)
    {
        $this->authorize('copy', $contract_template);

        return response()->json(
            $this->contractTemplate->copy($contract_template->id)
        );
    }
}
