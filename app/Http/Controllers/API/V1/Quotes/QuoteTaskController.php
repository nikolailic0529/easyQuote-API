<?php

namespace App\Http\Controllers\API\V1\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\{Task\CreateTaskRequest, Task\UpdateTaskRequest, TaskTemplate\UpdateTaskTemplateRequest,};
use App\Http\Resources\{V1\Task\TaskCollection, V1\Task\TaskWithIncludes,};
use App\Models\{Quote\Quote, Quote\WorldwideQuote, Task\Task};
use App\Queries\TaskQueries;
use App\Repositories\TaskTemplate\QuoteTaskTemplateStore as Template;
use App\Services\Exceptions\ValidationException;
use App\Services\Task\TaskEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Response};

class QuoteTaskController extends Controller
{
    public function __construct()
    {
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
    public function showTemplate(Template $template): JsonResponse
    {
        $this->authorize('view_quote_task_template');

        return response()->json($template);
    }

    /**
     * Update the task template for quotes.
     *
     * @param UpdateTaskTemplateRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateTemplate(UpdateTaskTemplateRequest $request, Template $template): JsonResponse
    {
        $this->authorize('update_quote_task_template');

        return response()->json(
            $template->setContent($request->input('form_data'))
        );
    }

    /**
     * Reset the task template for quotes to default.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function resetTemplate(Template $template): JsonResponse
    {
        $this->authorize('update_quote_task_template');

        return response()->json(
            $template->reset()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateTaskRequest $request
     * @param Quote $quote
     * @param TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeRescueQuoteTask(CreateTaskRequest $request, Quote $quote, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $quote);

        $resource = $service
            ->setCauser($request->user())
            ->createTaskForTaskable($request->getCreateTaskData(), $quote);

        return response()->json(TaskWithIncludes::make(
            $resource->load('user', 'users', 'attachments')
        ), Response::HTTP_CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateTaskRequest $request
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Services\Task\TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeWorldwideQuoteTask(CreateTaskRequest $request, WorldwideQuote $worldwideQuote, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $service
            ->setCauser($request->user())
            ->createTaskForTaskable($request->getCreateTaskData(), $worldwideQuote);

        return response()->json(TaskWithIncludes::make(
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
        $this->authorize('view', $task);

        return response()->json(TaskWithIncludes::make($task));
    }

    /**
     * Display the specified resource.
     *
     * @param Quote $quote
     * @param \App\Models\Task\Task $task
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showWorldwideQuoteTask(Quote $quote, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(TaskWithIncludes::make($task));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param Quote $quote
     * @param Task $task
     * @param TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateRescueQuoteTask(UpdateTaskRequest $request, Quote $quote, Task $task, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $quote);
        $this->authorize('update', $task);

        $resource = $service
            ->setCauser($request->user())
            ->updateTask($task, $request->getUpdateTaskData());

        return response()->json(
            TaskWithIncludes::make(
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
     * @param \App\Services\Task\TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateWorldwideQuoteTask(UpdateTaskRequest $request, WorldwideQuote $worldwideQuote, Task $task, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('update', $task);

        $resource = $service
            ->setCauser($request->user())
            ->updateTask($task, $request->getUpdateTaskData());

        return response()->json(
            TaskWithIncludes::make(
                $resource->load('user', 'users', 'attachments')
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Quote $quote
     * @param \App\Models\Task\Task $task
     * @param \App\Services\Task\TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyRescueQuoteTask(Quote $quote, Task $task, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $quote);
        $this->authorize('delete', $task);

        $service->deleteTask($task);

        return response()->json(true, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Models\Task\Task $task
     * @param TaskEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyWorldwideQuoteTask(WorldwideQuote $worldwideQuote, Task $task, TaskEntityService $service): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('delete', $task);

        $service->deleteTask($task);

        return response()->json(true, Response::HTTP_OK);
    }
}
