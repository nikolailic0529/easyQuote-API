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

    /**
     * Set the url attribute.
     *
     * @param string $url
     * @return self
     */
    public function url(string $url): self;

    /**
     * Get the title attribute.
     *
     * @return mixed
     */
    public function getTitle();

    /**
     * Get the status attribute.
     *
     * @return mixed
     */
    public function getStatus();

    /**
     * Get the url attribute.
     *
     * @return mixed
     */
    public function getUrl();

    /**
     * Get the image attribute.
     *
     * @return mixed
     */
    public function getImage();
}
