<?php

namespace App\Console\Commands\Pipeliner;

use App\Integrations\Pipeliner\Models\WebhookEntity;
use App\Services\Pipeliner\Webhook\WebhookRegistrarService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PlWebhookCommand extends Command
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

    protected function registerWebhook(WebhookRegistrarService $service): void
    {
        $url = $this->ask('Url', route('pipeliner.webhook'));

        $webhook = $service->registerWebhook($url);

        $this->outputWebhook($webhook);
    }

    protected function removeWebhook(WebhookRegistrarService $service): void
    {
        $webhookId = $this->argument('webhook-id');

        if (null === $webhookId) {
            throw new \InvalidArgumentException("Webhook id must be provided.");
        }

        $service->deleteWebhook($webhookId);

        $this->info("Webhook `$webhookId` removed.");
    }

    protected function listWebhooks(WebhookRegistrarService $service): void
    {
        $webhooks = $service->getWebhooks(
            $this->option('local') ? WebhookRegistrarService::LOCAL_ONLY : 0
        );

        $webhooks
            ->lazy()
            ->sortBy(static function (WebhookEntity $webhook): int {
                return $webhook->created->getTimestamp();
            })
            ->each($this->outputWebhook(...));
    }

    private function outputWebhook(WebhookEntity $entity): void
    {
        $this->getOutput()->horizontalTable(
            ['Id', 'Url', 'Events', 'Insecure SSL', 'Signature', 'Options', 'Created', 'Is Deleted'],
            [
                [
                    'id' => $entity->id,
                    'url' => $entity->url,
                    'events' => implode("\n", $entity->events),
                    'insecureSsl' => $entity->insecureSsl ? 'true' : 'false',
                    'signature' => $entity->signature,
                    'options' => json_encode($entity->options),
                    'created' => $entity->created->format('Y-m-d H:i:s'),
                    'isDeleted' => $entity->isDeleted ? '<bg=bright-red;fg=white> true </>' : '<bg=green;fg=white> false </>',
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
            new InputArgument('action', mode: InputArgument::REQUIRED,
                description: 'The command action (register,remove,list)'),
            new InputArgument('webhook-id', mode: InputArgument::OPTIONAL,
                description: 'The webhook id for remove action'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('local', mode: InputOption::VALUE_NEGATABLE, description: "Whether to list only local webhooks")
        ];
    }
}
