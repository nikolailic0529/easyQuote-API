<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskForTaskableRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\V1\Task\TaskListResource;
use App\Http\Resources\V1\Task\TaskWithIncludes;
use App\Models\Task\Task;
use App\Queries\TaskQueries;
use App\Services\Task\TaskEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * List tasks.
     *
     * @param Request $request
     * @param TaskQueries $queries
     * @return AnonymousResourceCollection
     */
    public function listTasksOfTaskable(Request $request, TaskQueries $queries, string $taskable): AnonymousResourceCollection
    {
        $collection = $queries->listTasksOfTaskableQuery($taskable, $request)->get();

        return TaskListResource::collection($collection);
    }

    /**
     * Show task.
     *
     * @param Task $task
     * @return \App\Http\Resources\V1\Task\TaskWithIncludes
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
     * @param CreateTaskForTaskableRequest $request
     * @param TaskEntityService $entityService
     * @return TaskWithIncludes
     * @throws \App\Services\Exceptions\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createTask(CreateTaskForTaskableRequest $request,
                               TaskEntityService            $entityService): TaskWithIncludes
    {
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
     * @param UpdateTaskRequest $request
     * @param TaskEntityService $entityService
     * @param Task $task
     * @return TaskWithIncludes
     * @throws \App\Services\Exceptions\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTask(UpdateTaskRequest $request,
                               TaskEntityService $entityService,
                               Task              $task): TaskWithIncludes
    {
        $this->authorize('update', $task);

        $task = $entityService
            ->setCauser($request->user())
            ->updateTask($task, $request->getUpdateTaskData());

        return TaskWithIncludes::make($task);
    }

    /**
     * Delete task.
     *
     * @param TaskEntityService $entityService
     * @param \App\Models\Task\Task $task
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteTask(TaskEntityService $entityService, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $entityService->deleteTask($task);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}