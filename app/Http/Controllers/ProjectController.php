<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectShowResource;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ProjectController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Project::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $projects = Project::query()
            ->with([
                'checklists.tasks.checklist.project',
                'adminUsers',
                'category',
                'checklists.departments',
                'comments.user',
                'departments.users.departments',
                'genre',
                'managerUsers',
                'project_files',
                'project_histories.user',
                'sector',
                'users.departments',
            ])
            ->get();

        return inertia('Projects/ProjectManagement', [
            'projects' => ProjectShowResource::collection($projects)->resolve(),

            'users' => User::all(),

            'categories' => Category::query()->with('projects')->get()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn ($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
        ]);
    }

    public function search_departments_and_users(SearchRequest $request): array
    {
        $this->authorize('viewAny', Department::class);
        $this->authorize('viewAny', User::class);

        return [
            'departments' => Department::search($request->input('query'))->get(),
            'users' => User::search($request->input('query'))->get()
        ];
    }

    public function search(SearchRequest $request)
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::search($request->input('query'))->get();

        return ProjectIndexResource::collection($projects)->resolve();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(StoreProjectRequest $request, HistoryService $historyService)
    {
        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = collect($request->assigned_departments)
            ->map(fn ($department) => Department::query()->findOrFail($department['id']))
            ->map(fn (Department $department) => $this->authorize('update', $department));

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
            'sector_id' => $request->sector_id,
            'category_id' => $request->category_id,
            'genre_id' => $request->genre_id,
        ]);
        $historyService->projectUpdated($project);

        $project->users()->save(Auth::user(), ['is_admin' => true, 'is_manager' => false]);

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        $project->departments()->sync($departments->pluck('id'));

        return Redirect::route('projects', $project)->with('success', 'Project created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  Project  $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Project $project, Request $request)
    {
        $project->load([
            'adminUsers',
            'category',
            'checklists.departments',
            'checklists.tasks.checklist.project',
            'checklists.tasks.user_who_done',
            'comments.user',
            'departments.users.departments',
            'genre',
            'managerUsers',
            'project_files',
            'project_histories.user',
            'sector',
            'users.departments',
        ]);

        return inertia('Projects/Show', [
            'project' => new ProjectShowResource($project),

            'categories' => Category::query()->with('projects')->get()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn ($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),

            'checklist_templates' => ChecklistTemplate::all()->map(fn ($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'task_templates' => $checklist_template->task_templates->map(fn ($task_template) => [
                    'id' => $task_template->id,
                    'name' => $task_template->name,
                    'description' => $task_template->description
                ]),
            ]),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),

            'openTab' => $request->openTab ?: 'checklist',
            'project_id' => $project->id,
            'opened_checklists' => User::where('id', Auth::id())->first()->opened_checklists,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Project  $project
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Project $project)
    {
        return inertia('Projects/Edit', [
            'project' => new ProjectEditResource($project),
            'users' => User::all(),
            'departments' => Department::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateProjectRequest  $request
     * @param  Project  $project
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project, HistoryService $historyService)
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants', 'cost_center', 'sector_id', 'category_id', 'genre_id');

        // authorization
        if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
            && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
            && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $project->fill($update_properties);
        $historyService->projectUpdated($project);
        $project->save();

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        if ($request->assigned_departments) {
            $project->departments()->sync(collect($request->assigned_departments)->pluck('id'));
        }

        return Redirect::back();
    }

    /**
     * Duplicates the project whose id is passed in the request
     */
    public function duplicate(Project $project, HistoryService $historyService)
    {
        // authorization
        if ($project->users->isNotEmpty()) {
            if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
                && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
                && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($project->departments->isNotEmpty()) {
            $project->departments->map(fn ($department) => $this->authorize('update', $department));
        }

        $newProject = Project::create([
            'name' => '(Kopie) ' . $project->name,
            'description' => $project->description,
            'number_of_participants' => $project->number_of_participants,
            'cost_center' => $project->cost_center,
            'sector_id' => $project->sector_id,
            'category_id' => $project->category_id,
            'genre_id' => $project->genre_id,
        ]);
        $historyService->projectUpdated($newProject);

        $project->checklists->map(function (Checklist $checklist) use ($newProject) {
            /** @var \App\Models\Checklist $replicated_checklist */
            $replicated_checklist = $checklist->replicate()->fill(['project_id' => $newProject->id]);
            $replicated_checklist->save();
            $replicated_checklist->departments()->sync($checklist->departments->pluck('id'));

            $checklist->tasks->map(function (Task $task) use ($replicated_checklist) {
                $replicated_task = $task->replicate(['deadline', 'done', 'done_at',])
                    ->fill(['checklist_id' => $replicated_checklist->id]);

                $replicated_task->save();
            });
        });

        $newProject->users()->attach([Auth::id() => ['is_admin' => true]]);

        $newProject->departments()->sync($project->departments->pluck('id'));
        $newProject->users()->sync($project->users->pluck('id'));

        $historyService->updateHistory($project, config('history.project.duplicated'));

        return Redirect::route('projects.show', $newProject->id)->with('success', 'Project created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Project  $project
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {
        $project->events()->delete();

        foreach ($project->checklists() as $checklist) {
            $checklist->tasks()->delete();
        }

        $project->checklists()->delete();

        $project->delete();

        return Redirect::route('projects')->with('success', 'Project moved to trash');
    }

    public function forceDelete(int $id)
    {
        /** @var Project $project */
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->forceDelete();
        $project->events()->withTrashed()->forceDelete();
        $project->project_histories()->delete();

        return Redirect::route('projects.trashed')->with('success', 'Project deleted');
    }

    public function restore(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->restore();
        $project->events()->withTrashed()->restore();

        return Redirect::route('projects.trashed')->with('success', 'Project restored');
    }

    public function getTrashed()
    {
        return inertia('Trash/Projects', [
            'trashed_projects' => ProjectIndexResource::collection(Project::onlyTrashed()->get())->resolve()
        ]);
    }
}