<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use App\Http\Resources\ChecklistShowResource;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Task;
use App\Support\Services\HistoryService;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\ResponseFactory;

class ChecklistController extends Controller
{
    protected ?NewHistoryService $history = null;

    public function __construct(protected readonly ChecklistService $checklistService)
    {
        $this->authorizeResource(Checklist::class);
    }

    public function create(): ResponseFactory
    {
        return inertia('Checklists/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('createProperties', Project::find($request->project_id));
        //Check whether checklist should be created on basis of a template
        if ($request->template_id) {
            $this->createFromTemplate($request);
        } else {
            $this->createWithoutTemplate($request);
        }

        $this->history = new NewHistoryService('App\Models\Project');
        $this->history->createHistory($request->project_id, 'Checkliste ' . $request->name. ' hinzugefügt');


        ProjectHistory::create([
            "user_id" => Auth::id(),
            "project_id" => $request->project_id,
            "description" => "Checkliste $request->name angelegt"
        ]);

        return Redirect::back()->with('success', 'Checklist created.');
    }

    /**
     * Creates a checklist on basis of a ChecklistTemplate
     * @param  Request  $request
     */
    protected function createFromTemplate(Request $request)
    {
        $template = ChecklistTemplate::where('id', $request->template_id)->first();
        $project = Project::where('id', $request->project_id)->first();

        $checklist = Checklist::create([
            'name' => $template->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

        foreach ($template->task_templates as $task_template) {
            Task::create([
                'name' => $task_template->name,
                'description' => $task_template->description,
                'done' => false,
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }

            $checklist->users()->sync(
                collect($template->users)
                    ->map(function ($user) {
                        return $user['id'];
                    })
            );
    }

    /**
     * Default creation of a checklist without a template
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    protected function createWithoutTemplate(Request $request)
    {
        $checklist = Checklist::create([
            'name' => $request->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

        foreach ($request->tasks as $task) {
            Task::create([
                'name' => $task['name'],
                'description' => $task['description'],
                'done' => false,
                'deadline' => $task['deadline_dt_local'],
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Checklist  $checklist
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Checklist $checklist)
    {
        return inertia('Checklists/Show', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Checklist  $checklist
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Checklist $checklist)
    {
        return inertia('Checklists/Edit', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function update(ChecklistUpdateRequest $request, Checklist $checklist)
    {
        $this->checklistService->updateByRequest($checklist, $request);

        if ($request->missing('assigned_user_ids')) {
            return Redirect::back()->with('success', 'Checklist updated');
        }

        $this->checklistService->assignUsersById($checklist, $request->assigned_user_ids);

        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory($checklist->project_id, 'Checkliste ' . $checklist->name . ' geändert');

        return Redirect::back()->with('success', 'Checklist updated');
    }

    public function destroy(Checklist $checklist, HistoryService $historyService): RedirectResponse
    {
        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory($checklist->project_id, 'Checkliste ' . $checklist->name . ' gelöscht');
        $checklist->delete();
        $historyService->checklistUpdated($checklist);

        return Redirect::back()->with('success', 'Checklist deleted');
    }
}
