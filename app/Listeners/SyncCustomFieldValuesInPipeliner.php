<?php

namespace App\Listeners;

use App\Events\CustomField\CustomFieldValuesUpdated;
use App\Integrations\Pipeliner\GraphQl\PipelinerDataIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerFieldIntegration;
use App\Services\Pipeliner\Strategies\PushCustomFieldStrategy;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\InteractsWithQueue;

class SyncCustomFieldValuesInPipeliner implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config                      $config,
                                protected PushCustomFieldStrategy     $pushCustomFieldStrategy,
                                protected PipelinerFieldIntegration   $fieldIntegration,
                                protected PipelinerDataIntegration    $dataIntegration)
    {
    }

    public function handle(CustomFieldValuesUpdated $event): void
    {
        if (false === $this->config->get('pipeliner.sync.custom_fields.enabled', true)) {
            return;
        }

        $field = $event->customField;

        if (false === key_exists($field->field_name, $this->config->get('pipeliner.sync.custom_fields.mapping', []))) {
            return;
        }

        $this->pushCustomFieldStrategy->sync($field);
    }
}
