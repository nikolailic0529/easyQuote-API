<?php

namespace App\Repositories\TaskTemplate;

class QuoteTaskTemplateStore extends TaskTemplateStore
{
    /**
     * Filepath to default store content.
     *
     * @return string
     */
    public function defaultContentPath(): string
    {
        return base_path('storage/_valuestore/quote.task.template.json');
    }
}
