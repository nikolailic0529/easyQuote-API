<?php

if (!function_exists('slack_client')) {
    function slack_client()
    {
        if (func_num_args() > 0) {
            return app('slack.client')->send(...func_get_args());
        }

        return app('slack.client');
    }
}
