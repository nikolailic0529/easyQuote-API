<?php

namespace App\Domain\Template\Repositories;

class QuoteTaskTemplateStore extends TaskTemplateStore
{
    /**
     * Filepath to default store content.
     */
    public function defaultContentPath(): string
    {
        return base_path('storage/_valuestore/quote.task.template.json');
    }
}
