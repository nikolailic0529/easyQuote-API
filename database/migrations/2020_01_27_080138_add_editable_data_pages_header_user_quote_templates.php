<?php

use Illuminate\Database\Migrations\Migration;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface as Repository;
use App\Models\QuoteTemplate\QuoteTemplate;

class AddEditableDataPagesHeaderUserQuoteTemplates extends Migration
{
    /**
     * Master Template Design.
     *
     * @var array
     */
    protected $masterDesign;

    public function __construct()
    {
        $this->masterDesign = json_decode(file_get_contents(database_path('seeds/models/template_designs.json')), true);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app(Repository::class)->allUserDefined(['*'], true)
            ->each(function ($template) {
                $this->handleTemplate($template);
            });
    }

    protected function handleTemplate(QuoteTemplate $template): void
    {
        $form_data = $template->form_data ?? [];

        $form_data['data_pages'] = $form_data['data_pages'] ?? [];

        $firstChild = head(data_get($this->masterDesign, 'form_data.data_pages'));

        array_unshift($form_data['data_pages'], $firstChild);

        tap($template)->unsetEventDispatcher()->update(compact('form_data'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
