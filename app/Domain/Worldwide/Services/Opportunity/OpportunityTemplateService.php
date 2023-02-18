<?php

namespace App\Domain\Worldwide\Services\Opportunity;

class OpportunityTemplateService
{
    protected string $templatePath;

    protected string $defaultTemplatePath;

    public function __construct(string $templatePath, string $defaultTemplatePath = '')
    {
        $this->templatePath = $templatePath;
        $this->defaultTemplatePath = $defaultTemplatePath;

        if (!file_exists($templatePath)) {
            file_put_contents($templatePath, json_encode($this->getDefaultOpportunityTemplateSchema()), LOCK_EX);
        }
    }

    public function getDefaultOpportunityTemplateSchema(): array
    {
        if (file_exists($this->defaultTemplatePath)) {
            return json_decode(file_get_contents($this->defaultTemplatePath), true);
        }

        return [];
    }

    public function getOpportunityTemplateSchema()
    {
        return json_decode(file_get_contents($this->templatePath), true);
    }

    public function updateOpportunityTemplateSchema(array $schema): void
    {
        file_put_contents($this->templatePath, json_encode($schema, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
