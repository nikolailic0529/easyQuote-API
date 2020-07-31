<?php

if (!function_exists('slack')) {
    /**
     * Slack notification client.
     *
     * @return App\Contracts\Services\SlackInterface
     */
    function slack()
    {
        if (func_num_args() > 0) {
            return app('slack.client')->send(...func_get_args());
        }

        return app('slack.client');
    }
}

if (!function_exists('notification')) {
    /**
     * Begin Pending Notification instance.
     *
     * @param array $attributes
     * @return \App\Contracts\Services\NotificationInterface
     */
    function notification(array $attributes = [])
    {
        return app('notification.storage')->setAttributes($attributes);
    }
}
