<?php

namespace App\Console\Commands\Pipeliner;

use App\Integrations\Pipeliner\Enum\EventEnum;
use App\Integrations\Pipeliner\GraphQl\PipelinerWebhookEntityIntegration;
use App\Integrations\Pipeliner\Models\CreateWebhookInput;
use App\Integrations\Pipeliner\Models\WebhookEntity;
use App\Services\Pipeliner\Webhook\WebhookEntityService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class WebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:pl-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manipulate the Pipeliner API webhooks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->resolveAction();

        match ($action) {
            'register' => $this->laravel->call($this->registerWebhook(...)),
            'remove' => $this->laravel->call($this->removeWebhook(...)),
            'list' => $this->laravel->call($this->listWebhooks(...)),
        };

        return self::SUCCESS;
    }

    protected function registerWebhook(PipelinerWebhookEntityIntegration $integration, WebhookEntityService $webhookService): void
    {
        $url = $this->ask('Url', route('pipeliner.webhook'));

        $input = new CreateWebhookInput(
            url: $url,
            events: [
                EventEnum::AccountUpdate,
                EventEnum::ContactUpdate,
                EventEnum::TaskCreate,
                EventEnum::TaskUpdate,
                EventEnum::TaskDelete,
                EventEnum::AppointmentCreate,
                EventEnum::AppointmentUpdate,
                EventEnum::AppointmentDelete,
            ],
            insecureSsl: true,
            signature: Str::uuid(),
            options: json_encode(config('pipeliner.webhook.options') ?? '{}')
        );

        $webhook = $integration->create($input);

        $webhookService->createWebhookFromPipelinerEntity($webhook);

        $this->outputWebhook($webhook);
    }

    protected function removeWebhook(PipelinerWebhookEntityIntegration $integration): void
    {
        $webhookId = $this->argument('webhook-id');

        if (null === $webhookId) {
            throw new \InvalidArgumentException("Webhook id must be provided.");
        }

        $integration->delete($webhookId);

        $this->info("Webhook `$webhookId` removed.");
    }

    protected function listWebhooks(PipelinerWebhookEntityIntegration $integration): void
    {
        $webhooks = $integration->getAll();

        collect($webhooks)
            ->sortBy(static function (WebhookEntity $webhook): int {
                return $webhook->created->getTimestamp();
            })
            ->each($this->outputWebhook(...));
    }

    private function outputWebhook(WebhookEntity $entity): void
    {
        $this->getOutput()->horizontalTable(
            ['Id', 'Url', 'Events', 'Insecure SSL', 'Signature', 'Options', 'Created'],
            [
                [
                    'id' => $entity->id,
                    'url' => $entity->url,
                    'events' => implode("\n", $entity->events),
                    'insecureSsl' => $entity->insecureSsl ? 'true' : 'false',
                    'signature' => $entity->signature,
                    'options' => json_encode($entity->options),
                    'created' => $entity->created->format('Y-m-d H:i:s'),
                ],
            ],
        );
    }

    protected function resolveAction(): ?string
    {
        $action = $this->argument('action');

        if (false === in_array($action, ['register', 'remove', 'list'], true)) {
            throw new \InvalidArgumentException("Unsupported action `$action` provided.");
        }

        return $action;
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('action', mode: InputArgument::REQUIRED, description: 'The command action (register,remove,list)'),
            new InputArgument('webhook-id', mode: InputArgument::OPTIONAL, description: 'The webhook id for remove action'),
        ];
    }
}
