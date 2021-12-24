<?php

namespace Tests\Unit;

use App\Services\Opportunity\OpportunityTemplateService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpportunityTemplateServiceTest extends TestCase
{
    /**
     * Test template schema writes default template schema when constructs,
     * if the template schema file is missing.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWritesDefaultTemplateSchemaWhenConstructs()
    {
        $testingDirectory = $this->app->storagePath().'/framework/testing/valuestore';
        $templateSchemaPath = "$testingDirectory/opportunity.template.json";
        $defaultSchemaPath = "$testingDirectory/opportunity.default.template.json";

        if (!file_exists($testingDirectory)) {
            mkdir($testingDirectory, 0777, true);
        }

        if (file_exists($templateSchemaPath)) {
            unlink($templateSchemaPath);
        }

        $defaultSchemaContent = json_encode([Str::uuid()]);

        file_put_contents($defaultSchemaPath, $defaultSchemaContent);

        $this->app->singleton(OpportunityTemplateService::class, function (Container $container) use ($defaultSchemaPath, $templateSchemaPath) {
            return new OpportunityTemplateService(
                $templateSchemaPath,
                $defaultSchemaPath
            );
        });

        $this->app->make(OpportunityTemplateService::class);

        $this->assertFileExists($templateSchemaPath);

        $this->assertStringContainsString($defaultSchemaContent, file_get_contents($templateSchemaPath));
    }
}
