<?php

namespace App\Domain\Task\Controllers\V1;

use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\Task\Queries\TaskQueries;
use App\Domain\Task\Requests\CreateTaskForTaskableRequest;
use App\Domain\Task\Requests\SetTaskReminderRequest;
use App\Domain\Task\Requests\UpdateTaskRequest;
use App\Domain\Task\Resources\V1\TaskListResource;
use App\Domain\Task\Resources\V1\TaskReminderResource;
use App\Domain\Task\Resources\V1\TaskWithIncludes;
use App\Domain\Task\Services\TaskEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class TaskController extends Controller
{
    /**
     * List tasks.
     */
    public function listTasksOfTaskable(
        Request $request,
        TaskQueries $queries,
        string $taskable
    ): AnonymousResourceCollection {
        $collection = $queries->listTasksOfTaskableQuery($taskable, $request)->get();

        return TaskListResource::collection($collection);
    }

    /**
     * Show task.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showTask(Task $task): TaskWithIncludes
    {
        $this->authorize('view', $task);

        return TaskWithIncludes::make($task);
    }

    /**
     * Create task for company.
     *
     * @throws \App\Foundation\Validation\Exceptions\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createTask(
        CreateTaskForTaskableRequest $request,
        TaskEntityService $entityService
    ): TaskWithIncludes {
        $this->authorize('view', $request->getTaskable());
        $this->authorize('create', Task::class);

        $task = $entityService
            ->setCauser($request->user())
            ->createTaskForTaskable(data: $request->getCreateTaskData(), linkedModel: $request->getTaskable());

        return TaskWithIncludes::make($task);
    }

    /**
     * Update task.
     *
     * @throws \App\Foundation\Validation\Exceptions\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTask(
        UpdateTaskRequest $request,
        TaskEntityService $entityService,
        Task $task
    ): TaskWithIncludes {
        $this->authorize('update', $task);

        $task = $entityService
            ->setCauser($request->user())
            ->updateTask($task, $request->getUpdateTaskData());

        return TaskWithIncludes::make($task);
    }

    /**
     * Delete task.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteTask(TaskEntityService $entityService, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $entityService->deleteTask($task);

        return response()->json(status: BaseResponse::HTTP_NO_CONTENT);
    }

    /**
     * Set task reminder.
     *
     * @throws AuthorizationException
     */
    public function setTaskReminder(
        SetTaskReminderRequest $request,
        TaskEntityService $entityService,
        TaskReminder $reminder,
    ): TaskReminderResource {
        $this->authorize('update', $reminder);

        $entityService->updateReminder($reminder, $request->getData());

        return TaskReminderResource::make($reminder);
    }

    /**
     * Delete task reminder.
     *
     * @throws AuthorizationException
     */
    public function deleteTaskReminder(
        Request $request,
        TaskEntityService $entityService,
        TaskReminder $reminder
    ): Response {
        $this->authorize('delete', $reminder);

        $entityService
            ->setCauser($request->user())
            ->deleteReminder($reminder);

        return response()->noContent();
    }
}
