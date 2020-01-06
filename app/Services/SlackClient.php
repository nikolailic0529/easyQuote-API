<?php

namespace App\Services;

use App\Contracts\Services\SlackInterface;
use Psr\Http\Message\ResponseInterface;
use Exception, Arr, Str;

class SlackClient implements SlackInterface
{
    /** @var array */
    protected $attributes = [];

    /** @var \GuzzleHttp\ClientInterface */
    protected $guzzle;

    public function __construct()
    {
        $this->guzzle = app(\GuzzleHttp\Client::class);
    }

    public function send(?array $attributes = null)
    {
        if(app()->runningUnitTests()) {
            return false;
        }

        if (isset($attributes)) {
            $this->attributes = $attributes;
        }

        if (!isset($this->attributes) || !Arr::has($this->attributes, ['title', 'status'])) {
            throw static::undefinedAttributes();
        }

        try {
            $response = $this->request($this->toArray());

            if (blank($response)) {
                $this->error();
                return false;
            }

            $this->success($response->getBody()->getContents());
            return true;
        } catch (Exception $e) {
            report_logger(['ErrorCode' => 'SNE_02'], ['ErrorDetails' => SNE_02 . ' — ' . $e->getMessage()]);

            return false;
        }
    }

    public function title($title): self
    {
        $this->attributes[__FUNCTION__] = $title;
        return $this;
    }

    public function status($status): self
    {
        $this->attributes[__FUNCTION__] = $status;
        return $this;
    }

    public function image(string $image): self
    {
        $this->attributes[__FUNCTION__] = $image;
        return $this;
    }

    protected function request(array $payload): ResponseInterface
    {
        return $this->guzzle->request('POST', SLACK_SERVICE_URL, $payload);
    }

    protected function success()
    {
        report_logger(['message' => SNS_01], ['payload' => $this->toArray()]);
    }

    protected function error()
    {
        report_logger(['ErrorCode' => 'SNE_01'], ['ErrorDetails' => SNE_01, 'payload' => $this->toArray()]);
    }

    protected function getTitle()
    {
        $title = optional($this->attributes)['title'];

        if (is_array($title)) {
            $title = implode(' ', $title);
        }

        return $title;
    }

    protected function getStatus()
    {
        $status = optional($this->attributes)['status'];

        if (is_array($status)) {
            $status = collect($status)->map(function ($value, $key) {
                $value = is_string($key) ? "{$key}: {$value}" : $value;
                $value .= !Str::endsWith($value, '.') ? '.' : '';
                return $value;
            })->implode(' ');
        }

        return $status;
    }

    protected function getUrl()
    {
        $url = optional($this->attributes)['url'];

        if (!isset($url)) {
            $url = config('app.url');
        }

        return $url;
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
                'image_url' => optional($this->attributes)['image'],
                'alt_text' => $this->getStatus()
            ]);
        }

        array_push($blocks, $body);

        return ['json' => compact('blocks')];
    }

    protected static function undefinedAttributes(): Exception
    {
        return new Exception('The title and status attributes must be defined.');
    }

    protected function setPayload(SlackMessage $message): void
    {
        $this->payload = $message->toArray();
    }
}
