<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\TaskRepositoryInterface as Tasks;
use App\Repositories\TaskTemplate\QuoteTaskTemplateStore as Template;
use App\Events\Task\{
    TaskCreated,
    TaskDeleted,
    TaskUpdated,
};
use App\Http\Requests\{
    Task\CreateTaskRequest,
    Task\UpdateTaskRequest,
    TaskTemplate\UpdateTaskTemplateRequest,
};
use App\Http\Resources\{
    Task\TaskCollection,
    Task\TaskResource,
};
use App\Models\{
    Quote\Quote,
    Task,
};
use App\Policies\QuoteTaskTemplatePolicy;
use Illuminate\Http\{
    Request,
    Response,
};
use Illuminate\Support\Facades\Gate;

class QuoteTaskController extends Controller
{
    protected Tasks $tasks;

    protected Template $template;

    public function __construct(Tasks $tasks, Template $template)
    {
        $this->tasks = $tasks;
        $this->template = $template;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        $resource = $this->tasks->paginate(['taskable_id' => $quote->getKey()], $request->query('search'));

        return response()->json(TaskCollection::make($resource));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('view_quote_task_template');

        return response()->json($this->template);
    }

    /**
     * Update the task template for quotes.
     *
     * @param UpdateTaskTemplateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplate(UpdateTaskTemplateRequest $request)
    {
        $this->authorize('update_quote_task_template');

        return response()->json(
            $this->template->setContent($request->form_data)
        );
    }

    /**
     * Reset the task template for quotes to default.
     *
     * @return \Illuminate\Http\Response
     */
    public function resetTemplate()
    {
        $this->authorize('update_quote_task_template');
        
        return response()->json(
            $this->template->reset()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateTaskRequest  $request
     * @param  Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTaskRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        $resource = tap(
            $this->tasks->create($request->validated(), $quote),
            fn (Task $task) => event(new TaskCreated($task))
        );

        return response()->json(TaskResource::make($resource->load('user', 'users', 'attachments')), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Quote $quote
     * @param  Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Quote $quote, Task $task)
    {
        $this->authorize('view', [$task, $quote]);

        return response()->json(TaskResource::make($task));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateTaskRequest  $request
     * @param  Quote $quote
     * @param  Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTaskRequest $request, Quote $quote, Task $task)
    {
        $this->authorize('update', $quote);
        $this->authorize('update', $task);

        $resource = tap(
            $this->tasks->update($task->getKey(), $request->validated()),
            fn (Task $task) => event(new TaskUpdated($task))
        );

        return response()->json(TaskResource::make($resource->load('user', 'users', 'attachments')));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Quote $quote
     * @param  Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quote $quote, Task $task)
    {
        $this->authorize('update', $quote);
        $this->authorize('delete', $task);

        $deleted = tap(
            $this->tasks->delete($task->getKey()),
            fn () => event(new TaskDeleted($task))
        );

        return response()->json($deleted);
    }
}
