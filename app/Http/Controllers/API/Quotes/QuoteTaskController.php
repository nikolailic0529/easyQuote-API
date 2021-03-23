<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\{Task\CreateTaskRequest, Task\UpdateTaskRequest, TaskTemplate\UpdateTaskTemplateRequest,};
use App\Http\Resources\{Task\TaskCollection, Task\TaskResource,};
use App\Models\{Quote\Quote, Quote\WorldwideQuote, Task};
use App\Queries\TaskQueries;
use App\Repositories\TaskTemplate\QuoteTaskTemplateStore as Template;
use App\Services\Exceptions\ValidationException;
use App\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Response};

class QuoteTaskController extends Controller
{
    protected Template $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * Paginate the existing Tasks on the specified Rescue Quote.
     *
     * @param Request $request
     * @param Quote $quote
     * @param TaskQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateRescueQuoteTasks(Request $request, Quote $quote, TaskQueries $queries): JsonResponse
    {
        $this->authorize('view', $quote);

        $resource = $queries->paginateTaskableTasksQuery($quote->getKey(), $request)->apiPaginate();

        return response()->json(TaskCollection::make($resource));
    }

    /**
     * @param Request $request
     * @param WorldwideQuote $worldwideQuote
     * @param TaskQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateWorldwideQuoteTasks(Request $request, WorldwideQuote $worldwideQuote, TaskQueries $queries): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $queries->paginateTaskableTasksQuery($worldwideQuote->getKey(), $request)->apiPaginate();

        return response()->json(TaskCollection::make($resource));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showTemplate(): JsonResponse
    {
        $this->authorize('view_quote_task_template');

        return response()->json($this->template);
    }

    /**
     * Update the task template for quotes.
     *
     * @param UpdateTaskTemplateRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateTemplate(UpdateTaskTemplateRequest $request): JsonResponse
    {
        $this->authorize('update_quote_task_template');

        return response()->json(
            $this->template->setContent($request->input('form_data'))
        );
    }

    /**
     * Reset the task template for quotes to default.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function resetTemplate(): JsonResponse
    {
        $this->authorize('update_quote_task_template');

        return response()->json(
            $this->template->reset()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateTaskRequest $request
     * @param Quote $quote
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeRescueQuoteTask(CreateTaskRequest $request, Quote $quote, TaskService $service): JsonResponse
    {
        $this->authorize('update', $quote);

        $resource = $service->createTaskForTaskable($request->getCreateTaskData(), $quote);

        return response()->json(TaskResource::make(
            $resource->load('user', 'users', 'attachments')
        ), Response::HTTP_CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateTaskRequest $request
     * @param WorldwideQuote $worldwideQuote
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeWorldwideQuoteTask(CreateTaskRequest $request, WorldwideQuote $worldwideQuote, TaskService $service): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $resource = $service->createTaskForTaskable($request->getCreateTaskData(), $worldwideQuote);

        return response()->json(TaskResource::make(
            $resource->load('user', 'users', 'attachments')
        ), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Quote $quote
     * @param Task $task
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showRescueQuoteTask(Quote $quote, Task $task): JsonResponse
    {
        $this->authorize('view', [$task, $quote]);

        return response()->json(TaskResource::make($task));
    }

    /**
     * Display the specified resource.
     *
     * @param Quote $quote
     * @param Task $task
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showWorldwideQuoteTask(Quote $quote, Task $task): JsonResponse
    {
        $this->authorize('view', [$task, $quote]);

        return response()->json(TaskResource::make($task));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param Quote $quote
     * @param Task $task
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateRescueQuoteTask(UpdateTaskRequest $request, Quote $quote, Task $task, TaskService $service): JsonResponse
    {
        $this->authorize('update', $quote);
        $this->authorize('update', $task);

        $resource = $service->updateTask($task, $request->getUpdateTaskData());

        return response()->json(
            TaskResource::make(
                $resource->load('user', 'users', 'attachments')
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param WorldwideQuote $worldwideQuote
     * @param Task $task
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateWorldwideQuoteTask(UpdateTaskRequest $request, WorldwideQuote $worldwideQuote, Task $task, TaskService $service): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);
        $this->authorize('update', $task);

        $resource = $service->updateTask($task, $request->getUpdateTaskData());

        return response()->json(
            TaskResource::make(
                $resource->load('user', 'users', 'attachments')
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Quote $quote
     * @param Task $task
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyRescueQuoteTask(Quote $quote, Task $task, TaskService $service): JsonResponse
    {
        $this->authorize('update', $quote);
        $this->authorize('delete', $task);

        $service->deleteTask($task);

        return response()->json(true, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param Task $task
     * @param TaskService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyWorldwideQuoteTask(WorldwideQuote $worldwideQuote, Task $task, TaskService $service): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);
        $this->authorize('delete', $task);

        $service->deleteTask($task);

        return response()->json(true, Response::HTTP_OK);
    }
}
