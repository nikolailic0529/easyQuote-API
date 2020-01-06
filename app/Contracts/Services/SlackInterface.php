<?php

namespace App\Contracts\Services;

interface SlackInterface
{
    /**
     * Send a message to Slack chanel.
     *
     * @param array|null $data
     * @return mixed
     */
    public function send(?array $attributes = null);

    /**
     * Set the Title attribute.
     *
     * @param mixed $title
     * @return self
     */
    public function title($title): self;

    /**
     * Set the Status attribute.
     *
     * @param mixed $status
     * @return self
     */
    public function status($status): self;

    /**
     * Set the image attribute.
     *
     * @param string $image
     * @return self
     */
    public function image(string $image): self;
}
