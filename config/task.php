<?php

return [
    'reminder' => [
        'schedule' => [
            'enabled' => env('TASK_REMINDER_SCHEDULE_ENABLED', true),
        ]
    ],
    'recurrence' => [
        'schedule' => [
            'enabled' => env('TASK_RECURRENCE_SCHEDULE_ENABLED', true),
        ]
    ]
];