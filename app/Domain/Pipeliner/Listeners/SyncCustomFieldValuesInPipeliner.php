<?php

namespace App\Domain\Pipeliner\Listeners;

use App\Domain\CustomField\Events\CustomFieldValuesUpdated;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerDataIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerFieldIntegration;
use App\Domain\Pipeliner\Services\Strategies\PushCustomFieldStrategy;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\InteractsWithQueue;

class SyncCustomFieldValuesInPipeliner implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config $config,
                                protected PushCustomFieldStrategy $pushCustomFieldStrategy,
                                protected PipelinerFieldIntegration $fieldIntegration,
                                protected PipelinerDataIntegration $dataIntegration)
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
