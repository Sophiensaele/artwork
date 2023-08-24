<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\MoneySourceFileResource;
use App\Models\BudgetSumDetails;
use App\Models\ColumnCell;
use App\Models\MainPosition;
use App\Models\MainPositionDetails;
use App\Models\MoneySource;
use App\Models\MoneySourceTask;
use App\Models\Project;
use App\Models\SubPosition;
use App\Models\SubPositionRow;
use App\Models\SubpositionSumDetail;
use App\Models\SumMoneySource;
use App\Models\Table;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use stdClass;
use function Clue\StreamFilter\fun;

class MoneySourceController extends Controller
{
    protected ?NotificationService $notificationService = null;
    protected ?stdClass $notificationData = null;
    protected ?NewHistoryService $history = null;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->notificationData = new \stdClass();
        $this->history = new NewHistoryService('App\Models\MoneySource');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('MoneySources/MoneySourceManagement', [
            'moneySources' => MoneySource::with(['users'])->get(),
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
        ]);
    }

    public function search(SearchRequest $request)
    {
        $filteredObjects = [];
        $this->authorize('viewAny', User::class);
        if ($request->input('type') === 'single') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    continue;
                }
                $filteredObjects[] = $moneySource;
            }
            return $filteredObjects;

        } else if ($request->input('type') === 'group') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        } else {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->projects->contains($request->projectId)) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        foreach ($request->users as $requestUser) {
            $user = User::find($requestUser['user_id']);
            // create user Notification
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => 'Du hast Zugriff auf "' . $request->name . '" erhalten'
            ];

            $this->notificationService->createNotification($user, 'Du hast Zugriff auf "' . $request->name . '" erhalten', [], NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED, 'green', [], false, '', null, $broadcastMessage);
            //$this->notificationService->create($user, $this->notificationData, $broadcastMessage);
        }


        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $user = Auth::user();
        $source = $user->money_sources()->create([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group
        ]);

        $source->users()->sync(collect($request->users));

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $source->id]);
            }
        }

        $this->history->createHistory($source->id, 'Finanzierungsquelle erstellt');

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(MoneySource $moneySource): \Inertia\Response|\Inertia\ResponseFactory
    {
        $moneySource->load([
            'money_source_files'
        ]);
        $amount = $moneySource->amount;
        $subMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        $columns = ColumnCell::where('linked_money_source_id', $moneySource->id)->get();

        $subPositionSumDetails = SubpositionSumDetail::with('subPosition.mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $mainPositionSumDetails = MainPositionDetails::with('mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $budgetSumDetails = BudgetSumDetails::with('column.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $linked_projects = [];
        $positions = [];
        $subMoneySourcePositions = [];
        $usersWithAccess = [];
        if ($moneySource->is_group) {
            foreach ($subMoneySources as $subMoneySource) {
                $columns = ColumnCell::where('linked_money_source_id', $subMoneySource->id)->get();
                foreach ($columns as $column) {
                    $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                    $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                    $mainPosition = MainPosition::find($subPosition->main_position_id);
                    $table = Table::find($mainPosition->table_id);
                    $project = Project::where('id', $table->project_id)->with(['users'])->first();
                    foreach ($project->users as $user) {
                        if (!$user->pivot->is_manager) {
                            continue;
                        }
                        $usersWithAccess[] = $user->id;
                    }
                    $linked_projects[] = [
                        'id' => $project->id,
                        'name' => $project->name,
                    ];
                    $subMoneySourcePositions[] = [
                        'type' => $column->linked_type,
                        'value' => $column->value,
                        'subPositionName' => $subPosition->name,
                        'mainPositionName' => $mainPosition->name,
                        'project' => [
                            'id' => $project->id,
                            'name' => $project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($column->created_at))
                    ];
                    if ($column->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$column->value;
                    } else {
                        $amount = (int)$amount - (int)$column->value;
                    }
                }
            }
        } else {

            foreach ($budgetSumDetails as $detail) {

                foreach ($detail->column->table->costSums as $costSum) {

                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $costSum,
                        'subPositionName' => "",
                        'mainPositionName' => "",
                        'project' => [
                            'id' => $detail->column->table->project->id,
                            'name' => $detail->column->table->project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];

                }

                foreach ($detail->column->table->earningSums as $costSum) {

                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $costSum,
                        'subPositionName' => "",
                        'mainPositionName' => "",
                        'project' => [
                            'id' => $detail->column->table->project->id,
                            'name' => $detail->column->table->project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];

                }

            }

            foreach ($subPositionSumDetails as $detail) {

                foreach ($detail->subPosition->columnSums as $columnSum) {

                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $columnSum['sum'],
                        'subPositionName' => 'Summe von ' . $detail->subPosition->name,
                        'mainPositionName' => $detail->subPosition->mainPosition->name,
                        'project' => [
                            'id' => $detail->subPosition->mainPosition->table->project->id,
                            'name' => $detail->subPosition->mainPosition->table->project->name,
                        ],
                        'is_sum' => true,
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];

                }

            }

            foreach ($mainPositionSumDetails as $detail) {

                foreach ($detail->mainPosition->columnSums as $columnSum) {

                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $columnSum['sum'],
                        'subPositionName' => "",
                        'mainPositionName' => "Summe von " .$detail->mainPosition->name,
                        'project' => [
                            'id' => $detail->mainPosition->table->project->id,
                            'name' => $detail->mainPosition->table->project->name,
                        ],
                        'is_sum' => true,
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];

                }

            }

            foreach ($columns as $column) {
                $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                $mainPosition = MainPosition::find($subPosition->main_position_id);
                $table = Table::find($mainPosition->table_id);

                $project = Project::where('id', $table->project_id)->with(['users'])->first();
                foreach ($project->users as $user) {
                    if (!$user->pivot->is_manager) {
                        continue;
                    }
                    $usersWithAccess[] = $user->id;
                }
                $linked_projects[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                ];
                $positions[] = [
                    'type' => $column->linked_type,
                    'value' => $column->value,
                    'subPositionName' => $subPosition->name,
                    'mainPositionName' => $mainPosition->name,
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'column_name' => $column->column->name,
                    'created_at' => date('d.m.Y', strtotime($column->created_at))
                ];

                if ($column->linked_type === 'EARNING') {
                    $amount = (int)$amount + (int)$column->value;
                } else {
                    $amount = (int)$amount - (int)$column->value;
                }
            }



        }

        $historyArray = [];
        $historyComplete = $moneySource->historyChanges()->all();

        foreach ($historyComplete as $history) {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return inertia('MoneySources/Show', [
            'moneySource' => [
                'id' => $moneySource->id,
                'creator' => User::find($moneySource->creator_id),
                'name' => $moneySource->name,
                'amount' => $moneySource->amount,
                'amount_available' => $amount,
                'source_name' => $moneySource->source_name,
                'start_date' => $moneySource->start_date,
                'end_date' => $moneySource->end_date,
                'users' => $moneySource->users()->get(),
                'group_id' => $moneySource->group_id,
                'money_source_files' => MoneySourceFileResource::collection($moneySource->money_source_files),
                'moneySourceGroup' => MoneySource::find($moneySource->group_id),
                'subMoneySources' => $subMoneySources->map(fn($source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                ]),
                'description' => $moneySource->description,
                'is_group' => $moneySource->is_group,
                'created_at' => $moneySource->created_at,
                'updated_at' => $moneySource->updated_at,
                'tasks' => MoneySourceTask::with('money_source_task_users')->where('money_source_id', $moneySource->id)->get()->map(fn($task) => [
                    'id' => $task->id,
                    'money_source_id' => $task->money_source_id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'deadline' => $task->deadline,
                    'done' => (bool)$task->done,
                    'money_source_task_users' => $task->money_source_task_users,
                    'pivot' => $task->pivot
                ]),
                'positions' => $positions,
                'subMoneySourcePositions' => $subMoneySourcePositions,
                'linked_projects' => array_unique($linked_projects, SORT_REGULAR),
                'usersWithAccess' => array_unique($usersWithAccess, SORT_NUMERIC),
                'history' => $historyArray
            ],
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
            'moneySources' => MoneySource::where('is_group', false)->get(),
            'projects' => Project::all()->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'linkedProjects' => $moneySource->projects()->get()
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     */
    public function edit(MoneySource $moneySource)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MoneySource $moneySource
     */
    public function update(Request $request, MoneySource $moneySource)
    {

        $oldName = $moneySource->name;
        $oldDescription = $moneySource->description;
        $oldAmount = $moneySource->amount;

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }

        $moneySource->users()->sync(collect($request->users));

        $moneySource->update([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'group_id' => $request->group_id,
        ]);

        $newName = $moneySource->name;
        $newDescription = $moneySource->description;
        $newAmount = $moneySource->amount;

        if ($oldName !== $newName) {
            $this->history->createHistory($moneySource->id, 'Finanzierungsquellenname geändert');
        }

        if ($oldDescription !== $newDescription && !empty($newDescription) && !empty($oldDescription)) {
            $this->history->createHistory($moneySource->id, 'Beschreibung geändert');
        }

        if (empty($oldDescription) && !empty($newDescription)) {
            $this->history->createHistory($moneySource->id, 'Beschreibung hinzugefügt');
        }
        if (!empty($oldDescription) && empty($newDescription)) {
            $this->history->createHistory($moneySource->id, 'Beschreibung gelöscht');
        }

        if($oldAmount !== $newAmount){
            $this->history->createHistory($moneySource->id, 'Ursprungsvolumen geändert');
        }

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $moneySource->id]);
            }
        }

    }

    public function updateUsers(Request $request, MoneySource $moneySource)
    {
        $moneySource->users()->sync(collect($request->users));
        $tasks = $moneySource->money_source_tasks()->get();
        foreach ($tasks as $task){
            $task->money_source_task_users()->sync($moneySource->competent()->get());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MoneySource $moneySource): \Illuminate\Http\RedirectResponse
    {
        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }
        $users = json_decode($moneySource->users);
        if ($users) {
            foreach ($users as $user) {
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => 'Finanzierungsquelle/gruppe ' . $moneySource->name . ' wurde gelöscht'
                ];
                $this->notificationService->createNotification(User::find($user->id), 'Finanzierungsquelle/gruppe ' . $moneySource->name . ' wurde gelöscht', [], NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED, 'red', [], false, '', null, $broadcastMessage);
                //$this->notificationService->create(User::find($user->id), $this->notificationData, $broadcastMessage);
            }
        }
        $moneySource->delete();
        return Redirect::route('money_sources.index')->with('success', 'MoneySource deleted.');
    }

    public function duplicate(MoneySource $moneySource): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $newMoneySource = $user->money_sources()->create([
            'name' => '(Kopie) ' . $moneySource->name,
            'amount' => $moneySource->amount,
            'start_date' => $moneySource->start_date,
            'end_date' => $moneySource->end_date,
            'source_name' => $moneySource->source_name,
            'description' => $moneySource->description,
            'is_group' => $moneySource->is_group,
            'group_id' => $moneySource->group_id,
            'users' => $moneySource->users
        ]);

        return Redirect::route('money_sources.index')->with('success', 'MoneySource duplicated.');
    }


    private function checkUserChanges($moneySource, $oldUsers, $newUsers)
    {
        $oldUserIds = [];
        $newUserIds = [];

        foreach ($oldUsers as $oldUser) {
            $oldUserIds[] = $oldUser->id;
        }

        foreach ($newUsers as $newUser) {
            $newUserIds[] = $newUser->id;
            if (!in_array($newUser->id, $oldUserIds)) {
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => 'Du hast Zugriff auf ' . $moneySource->name . ' erhalten'
                ];
                $this->notificationService->createNotification(User::where('id', $newUser->id)->first(), 'Du hast Zugriff auf ' . $moneySource->name . ' erhalten', [], NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED, 'green', [], false, '', null, $broadcastMessage);
                //$this->notificationService->create(User::where('id', $newUser->id)->first(), $this->notificationData, $broadcastMessage);
                $this->history->createHistory($moneySource->id, 'Nutzerzugriff zu Finanzierungsquelle hinzugefügt');
            }
        }

        foreach ($oldUserIds as $oldUserId) {
            if (!in_array($oldUserId, $newUserIds)) {
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'danger',
                    'message' => 'Dein Zugriff auf ' . $moneySource->name . ' wurde gelöscht'
                ];
                $this->notificationService->createNotification(User::where('id', $newUser->id)->first(), 'Dein Zugriff auf ' . $moneySource->name . ' wurde gelöscht', [], NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED, 'red', [], false, '', null, $broadcastMessage);
                //$this->notificationService->create(User::where('id', $oldUserId)->first(), $this->notificationData, $broadcastMessage);
                $this->history->createHistory($moneySource->id, 'Nutzerzugriff zu Finanzierungsquelle entfernt');
            }

        }
    }

    public function updateProjects(MoneySource $moneySource, Request $request)
    {
        $moneySource->projects()->sync($request->linkedProjectIds);
    }

}
