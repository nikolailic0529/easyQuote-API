<?php

namespace App\Domain\Slack\Services;

use App\Domain\Slack\Contracts\SlackInterface;
use App\Domain\Slack\Events\FailedSend;
use App\Domain\Slack\Events\Sent;
use App\Domain\Slack\Jobs\SendSlackNotification;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class SlackClient implements SlackInterface
{
    protected array $attributes = [];

    protected bool $forceEnabled = false;

    public function send(?array $attributes = null)
    {
        if ($this->disabled()) {
            return false;
        }

        if (isset($attributes)) {
            $this->attributes = $attributes;
        }

        $this->validateAttributes();

        try {
            if (app()->runningUnitTests() && !$this->forceEnabled) {
                $this->success();

                return true;
            }

            $response = $this->performRequest($this->toArray());

            if ($response->failed()) {
                $this->error();

                return false;
            }

            $this->success();

            return true;
        } catch (\Throwable $e) {
            customlog(['ErrorCode' => 'SNE-02'], ['ErrorDetails' => customlog()->formatError(SNE_02, $e)]);

            return false;
        }
    }

    public function queue(?array $attributes = null): void
    {
        if ($this->disabled()) {
            return;
        }

        if (isset($attributes)) {
            $this->attributes = $attributes;
        }

        $this->validateAttributes();

        dispatch(new SendSlackNotification($this->attributes));
    }

    public function title($title)
    {
        $this->attributes[__FUNCTION__] = $title;

        return $this;
    }

    public function status($status)
    {
        $this->attributes[__FUNCTION__] = $status;

        return $this;
    }

    public function image(string $image)
    {
        $this->attributes[__FUNCTION__] = $image;

        return $this;
    }

    public function url(string $url)
    {
        $this->attributes[__FUNCTION__] = $url;

        return $this;
    }

    public function getTitle()
    {
        $title = $this->getAttribute('title');

        if (is_iterable($title)) {
            $title = Collection::wrap($title)->implode(' ');
        }

        return $title;
    }

    public function getStatus()
    {
        $status = $this->getAttribute('status');

        if (is_iterable($status)) {
            $status = Collection::wrap($status)
                ->map(
                    static fn ($value, $key) => (string) Str::of($value)
                        ->when(is_string($key), static fn (Stringable $string) => $string->prepend($key, ': '))
                        ->finish('.')
                )->implode(PHP_EOL);
        }

        return $status;
    }

    public function getUrl()
    {
        $url = $this->getAttribute('url');

        if (!isset($url)) {
            $url = config('app.url');
        }

        return $url;
    }

    public function getImage()
    {
        return $this->getAttribute('image');
    }

    public function enable()
    {
        $this->forceEnabled = true;

        return $this;
    }

    public function disable()
    {
        $this->forceEnabled = false;

        return $this;
    }

    protected function validateAttributes(): void
    {
        if (!isset($this->attributes) || !Arr::has($this->attributes, ['title', 'status'])) {
            throw static::undefinedAttributes();
        }
    }

    protected function getAttribute(string $key)
    {
        return Arr::get($this->attributes, $key);
    }

    protected function performRequest(array $payload): Response
    {
        return Http::asJson()->post(config('services.slack.endpoint'), $payload);
    }

    protected function success(): void
    {
        event(new Sent($this->toArray()));

        customlog(['message' => SNS_01], ['payload' => $this->toArray()]);
    }

    protected function error(): void
    {
        event(new FailedSend($this->toArray()));

        customlog(['ErrorCode' => 'SNE_01'], ['ErrorDetails' => SNE_01, 'payload' => $this->toArray()]);
    }

    protected function toArray()
    {
        $json = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $this->getTitle(),
                ],
            ],
            [
                'type' => 'section',
                'block_id' => 'section_body',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => static::formatBody($this->getUrl(), $this->getTitle(), $this->getStatus()),
                ],
                'accessory' => transform($this->getImage(), fn () => [
                    'type' => 'image',
                    'image_url' => $this->getImage(),
                    'alt_text' => $this->getTitle(),
                ]),
            ],
        ];

        $json = array_map(static fn ($block) => array_filter($block), $json);

        return ['blocks' => $json];
    }

    protected function disabled(): bool
    {
        if ($this->forceEnabled) {
            return false;
        }

        if (config('services.slack.enabled') === false) {
            return true;
        }

        return false;
    }

    protected static function formatBody($url, $title, $status): string
    {
        return sprintf('<%s|%s> %s%s', $url, $title, PHP_EOL, $status);
    }

    protected static function undefinedAttributes(): \RuntimeException
    {
        return new \RuntimeException('The title and status attributes must be defined.');
    }
}
