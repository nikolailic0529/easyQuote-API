<?php

namespace App\Http\Controllers\API\Templates;

use App\Contracts\Repositories\QuoteTemplate\HpeContractTemplate as Templates;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteTemplate\{
    DeleteTemplate,
    FilterHpeTemplates,
    HpeTemplateDesign,
    StoreHpeContractTemplate,
    UpdateHpeContractTemplate,
};
use App\Http\Resources\TemplateRepository\TemplateCollection;
use App\Http\Resources\TemplateRepository\TemplateResourceWithIncludes;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ProfileHelper;

class HpeContractTemplateController extends Controller
{
    protected Templates $templates;

    public function __construct(Templates $templates)
    {
        $this->templates = $templates;

        $this->authorizeResource(HpeContractTemplate::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(
            TemplateCollection::make($this->templates->paginate($request->query('search')))
        );
    }

    /**
     * Filter Hpe Contract Templates by specified clause.
     *
     * @param FilterHpeTemplates $request
     * @return void
     */
    public function filterTemplates(FilterHpeTemplates $request)
    {
        return response()->json(
            $request->getFilteredTemplates()
        );
    }

    /**
     * Display a listing of the resource by specified country.
     *
     * @param  string $country
     * @return \Illuminate\Http\JsonResponse
     */
    public function country(string $country)
    {
        return response()->json(
            $this->templates->findByCountry($country)
        );
    }

    /**
     * Retrieve data for template designer.
     *
     * @param  HpeTemplateDesign $request
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function designer(HpeTemplateDesign $request, HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            $request->getDesign()
        );
    }

    /**
     * Create a duplicate of the specified resource in repository.
     *
     * @param \App\Models\QuoteTemplate\ContractTemplate $hpeContractTemplate
     * @return \Illuminate\Http\Response
     */
    public function copy(HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            $this->templates->copy($hpeContractTemplate)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreHpeContractTemplate $request)
    {
        $resource = $this->templates->create($request->validated(), JsonResponse::HTTP_CREATED);

        return response()->json(
            TemplateResourceWithIncludes::make($resource),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            TemplateResourceWithIncludes::make($hpeContractTemplate)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateHpeContractTemplate $request, HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            TemplateResourceWithIncludes::make(
                $this->templates->update($hpeContractTemplate, $request->validated())
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteTemplate $request
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteTemplate $request, HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            tap(
                $this->templates->delete($hpeContractTemplate),
                fn () => ProfileHelper::flushHpeContractTemplateProfiles($hpeContractTemplate)
            )
        );
    }

    /**
     * Mark as activated the specified resource in storage.
     *
     * @param  HpeContractTemplate $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            $this->templates->activate($hpeContractTemplate)
        );
    }

    /**
     * Mark as deactivated the specified resource in storage.
     *
     * @param  HpeContractTemplate $hpeContractTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(HpeContractTemplate $hpeContractTemplate)
    {
        return response()->json(
            tap(
                $this->templates->deactivate($hpeContractTemplate),
                fn () => ProfileHelper::flushHpeContractTemplateProfiles($hpeContractTemplate, 'deactivated')
            )
        );
    }
}
