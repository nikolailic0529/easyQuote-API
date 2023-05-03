<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Queries\TaskQueries;
use App\Domain\Task\Requests\CreateTaskRequest;
use App\Domain\Task\Requests\UpdateTaskRequest;
use App\Domain\Task\Resources\V1\TaskCollection;
use App\Domain\Task\Resources\V1\TaskWithIncludes;
use App\Domain\Task\Services\TaskEntityService;
use App\Domain\Template\Repositories\QuoteTaskTemplateStore as Template;
use App\Domain\Template\Requests\{TaskTemplate\UpdateTaskTemplateRequest};
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Http\Controller;
use App\Foundation\Validation\Exceptions\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QuoteTaskController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Paginate the existing Tasks on the specified Rescue Quote.
     *
     * @throws AuthorizationException
     */
    public function paginateRescueQuoteTasks(Request $request, Quote $quote, TaskQueries $queries): JsonResponse
    {
        $this->authorize('view', $quote);

        $resource = $queries->paginateTaskableTasksQuery($quote->getKey(), $request)->apiPaginate();

        return response()->json(TaskCollection::make($resource));
    }

    /**
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
     * @throws AuthorizationException
     */
    public function showTemplate(Template $template): JsonResponse
    {
        return response()->json($template);
    }

    /**
     * Update the task template for quotes.
     *
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
     * @throws AuthorizationException
     * @throws \App\Foundation\Validation\Exceptions\ValidationException
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
