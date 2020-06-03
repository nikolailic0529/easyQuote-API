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
     * Queue a newly created Slack message.
     *
     * @param array|null $attributes
     * @return mixed
     */
    public function queue(?array $attributes = null);

    /**
     * Set the Title attribute.
     *
     * @param mixed $title
     * @return $this
     */
    public function title($title);

    /**
     * Set the Status attribute.
     *
     * @param mixed $status
     * @return $this
     */
    public function status($status);

    /**
     * Set the image attribute.
     *
     * @param string $image
     * @return $this
     */
    public function image(string $image);

    /**
     * Set the url attribute.
     *
     * @param string $url
     * @return $this
     */
    public function url(string $url);

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
