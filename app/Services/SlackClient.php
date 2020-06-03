<?php

namespace App\Services;

use App\Contracts\Services\SlackInterface;
use App\Jobs\SendSlackNotification;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Arr, Str;

class SlackClient implements SlackInterface
{
    protected array $attributes = [];

    public function send(?array $attributes = null)
    {
        if($this->disabled()) {
            return false;
        }

        if (isset($attributes)) {
            $this->attributes = $attributes;
        }

        $this->validateAttributes();

        try {
            $response = $this->request($this->toArray());

            if ($response->failed() || blank($response->json())) {
                $this->error();

                return false;
            }

            $this->success();

            return true;
        } catch (Exception $e) {
            report_logger(['ErrorCode' => 'SNE_02'], ['ErrorDetails' => SNE_02 . ' — ' . $e->getMessage()]);

            return false;
        }
    }

    public function queue(?array $attributes = null)
    {
        if($this->disabled()) {
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
        $title = optional($this->attributes)['title'];

        if (is_array($title)) {
            $title = implode(' ', $title);
        }

        return $title;
    }

    public function getStatus()
    {
        $status = optional($this->attributes)['status'];

        if (is_array($status)) {
            $status = collect($status)->map(function ($value, $key) {
                $value = is_string($key) ? "{$key}: {$value}" : $value;
                $value .= !Str::endsWith($value, '.') ? '.' : '';
                return $value;
            })->implode(PHP_EOL);
        }

        return $status;
    }

    public function getUrl()
    {
        $url = optional($this->attributes)['url'];

        if (!isset($url)) {
            $url = config('app.url');
        }

        return $url;
    }

    public function getImage()
    {
        return optional($this->attributes)['image'];
    }

    protected function validateAttributes(): void
    {
        if (!isset($this->attributes) || !Arr::has($this->attributes, ['title', 'status'])) {
            throw static::undefinedAttributes();
        }
    }

    protected function request(array $payload): Response
    {
        return Http::post(config('services.slack.endpoint'), $payload);
    }

    protected function success()
    {
        report_logger(['message' => SNS_01], ['payload' => $this->toArray()]);
    }

    protected function error()
    {
        report_logger(['ErrorCode' => 'SNE_01'], ['ErrorDetails' => SNE_01, 'payload' => $this->toArray()]);
    }

    protected function toArray()
    {
        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $this->getTitle()
                ]
            ]
        ];

        $body = [
            'type' => 'section',
            'block_id' => 'section_body',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '<' . $this->getUrl() . '|' . $this->getTitle() . '> ' . PHP_EOL . $this->getStatus()
            ]
        ];

        if (isset($this->attributes['image'])) {
            data_set($body, 'accessory', [
                'type' => 'image',
                'image_url' => $this->getImage(),
                'alt_text' => $this->getTitle()
            ]);
        }

        array_push($blocks, $body);

        return ['json' => compact('blocks')];
    }

    protected static function undefinedAttributes(): RuntimeException
    {
        return new RuntimeException('The title and status attributes must be defined.');
    }

    protected function setPayload(SlackMessage $message): void
    {
        $this->payload = $message->toArray();
    }

    protected function disabled(): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        if (config('services.slack.enabled') === false) {
            return true;
        }

        return false;
    }
}
