<?php

namespace App\Services;

use App\Models\{
    Task,
    Quote\Quote
};

class TaskService
{
    public function qualifyTaskableModel(Task $task): string
    {
        return class_basename($task->taskable);
    }

    public function qualifyTaskableUnique(Task $task): ?string
    {
        if ($task->taskable instanceof Quote) {
            return sprintf('RFQ %s', $task->taskable->customer->rfq);
        }

        return null;
    }

    public function qualifyTaskableRoute(Task $task): ?string
    {
        if ($task->taskable instanceof Quote) {
            return ui_route('quotes.status', ['quote' => $task->taskable]);
        }

        return null;
    }

    public function qualifyTaskableName(Task $task): string
    {
        return implode(' ', [$this->qualifyTaskableModel($task), $this->qualifyTaskableUnique($task)]);
    }
}
