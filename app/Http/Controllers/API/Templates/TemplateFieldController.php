<?php namespace App\Http\Controllers\API\Templates;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository;
use App\Http\Requests\QuoteTemplate \ {
    StoreTemplateFieldRequest,
    UpdateTemplateFieldRequest
};

class TemplateFieldController extends Controller
{
    protected $templateField;

    public function __construct(TemplateFieldRepository $templateField)
    {
        $this->templateField = $templateField;
    }

    /**
     * Display a listing of the User's Template Fields.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->templateField->search(request('search'))
            );
        }

        return response()->json(
            $this->templateField->all()
        );
    }

    /**
     * Data for creating a new Template Field
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->json(
            $this->templateField->data()
        );
    }

    /**
     * Store a newly created Template Field in storage.
     *
     * @param  StoreTemplateFieldRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTemplateFieldRequest $request)
    {
        return response()->json(
            $this->templateField->create($request)
        );
    }

    /**
     * Display the specified Template Field.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->templateField->find($id)
        );
    }

    /**
     * Update the specified Template Field in storage.
     *
     * @param  UpdateTemplateFieldRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTemplateFieldRequest $request, string $id)
    {
        return response()->json(
            $this->templateField->update($request, $id)
        );
    }

    /**
     * Remove the specified Template Field from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->templateField->delete($id)
        );
    }

    /**
     * Activate the specified Template Field from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->templateField->activate($id)
        );
    }

    /**
     * Deactivate the specified Template Field from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->templateField->deactivate($id)
        );
    }
}
