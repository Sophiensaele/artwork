<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationGroupEnum;
use App\Http\Resources\CalendarEventResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\NotificationProjectResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Notifications\BudgetVerified;
use App\Notifications\ConflictNotification;
use App\Notifications\DeadlineNotification;
use App\Notifications\EventNotification;
use App\Notifications\MoneySourceNotification;
use App\Notifications\ProjectNotification;
use App\Notifications\RoomNotification;
use App\Notifications\RoomRequestNotification;
use App\Notifications\TaskNotification;
use App\Notifications\TeamNotification;
use Artwork\Modules\Notification\Models\GlobalNotification;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Inertia\ResponseFactory;

class NotificationController extends Controller
{
    public function __construct(private readonly VacationService $vacationService)
    {
    }
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function index(): Response|ResponseFactory
    {
        $historyObjects = [];
        $event = null;
        // reload functions
        if (request('showHistory')) {
            if (request('historyType') === 'project') {
                $project = Project::find(request('modelId'));
                $historyComplete = $project->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyObjects[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                $historyComplete = $event->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyObjects[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }


            if (request('historyType') === 'vacations') {
                $vacations = $this->vacationService->findVacationsByUserId(request('modelId'));

                foreach ($vacations as $vacation) {
                    $historyComplete = $vacation->historyChanges()->all();
                    foreach ($historyComplete as $history) {
                        $historyObjects[] = [
                            'changes' => json_decode($history->changes),
                            'created_at' => $history->created_at->diffInHours() < 24
                                ? $history->created_at->diffForHumans()
                                : $history->created_at->format('d.m.Y, H:i'),
                        ];
                    }
                }
            }
        }

        if (request('openDeclineEvent')) {
            $event = Event::find(request('eventId'));
        }

        if (request('openEditEvent')) {
            $event = Event::find(request('eventId'));
        }

        $globalNotification = GlobalNotification::first();
        $globalNotification['image_url'] = $globalNotification?->image_name ?
            Storage::disk('public')->url($globalNotification->image_name) :
            null;

        /** @var User $user */
        $user = Auth::user();
        $output = [];
        $outputRead = [];

        $user->notifications()->latest()->limit(10)->get();

        foreach ($user->notifications as $notification) {
            if ($notification->read_at === null) {
                $output[$notification->data['groupType']][] = $notification;
            } else {
                $outputRead[$notification->data['groupType']][] = $notification;
            }
        }

        return inertia('Notifications/Show', [
            'historyObjects' => $historyObjects,
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'project',
            'wantedSplit',
            'roomCollisions',
            'notifications' => $output,
            'readNotifications' => $outputRead,
            'globalNotification' => $globalNotification,
            'rooms' => RoomIndexWithoutEventsResource::collection(Room::all())->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => NotificationProjectResource::collection(Project::all())->resolve(),
            'notificationSettings' => $user->notificationSettings()->get()->groupBy("group_type"),
            'notificationFrequencies' => array_map(fn (NotificationFrequency $frequency) => [
                'title' => $frequency->title(),
                'value' => $frequency->value,
            ], NotificationFrequency::cases()),
            'groupTypes' => collect(NotificationGroupEnum::cases())->reduce(
                function ($groupTypes, $type) {
                    $groupTypes[$type->value] = [
                        'title' => $type->title(),
                        'description' => $type->description(),
                    ];
                    return $groupTypes;
                },
                []
            ),
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function create($user, object $notificationData, ?array $broadcastMessage = []): void
    {
        switch ($notificationData->type) {
            case NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT_CHANGED:
                $historyArray = [];
                $historyComplete = [];
                if ($notificationData->event) {
                    $historyComplete = $notificationData->event->historyChanges()->all();
                }

                foreach ($historyComplete as $history) {
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }

                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'eventHistory' => $historyArray,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new EventNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_CHANGED:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => [
                        'title' => $notificationData->task->title,
                        'deadline' => $notificationData->task->deadline
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_PROJECT:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'team' => [
                        'id' => $notificationData->team->id,
                        'title' => $notificationData->team->title,
                        'svg_name' => $notificationData->team->svg_name,
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TeamNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_CHANGED:
                $room = $notificationData->room->id;
                $historyArray = [];
                $historyComplete = Room::find($room)->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'room' => $notificationData->room,
                    'history' => $historyArray,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'conflict' => $notificationData->conflict,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ConflictNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_REMINDER:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => $notificationData->task,
                ];
                Notification::send($user, new DeadlineNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_NEW_TASK:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new MoneySourceNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'requested_position' => $notificationData->requested_position,
                    'project' => $notificationData->project,
                    'created_by' => $notificationData->created_by,
                    'requested_id' => $notificationData->requested_id,
                    'position' => $notificationData->position,
                    'changeType' => $notificationData->changeType
                ];
                Notification::send($user, new BudgetVerified($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT:
                $project = $notificationData->project->id;
                $historyArray = [];
                $projectFind = Project::find($project);
                if (!empty($projectFind)) {
                    $historyComplete = $projectFind->historyChanges()->all();
                    foreach ($historyComplete as $history) {
                        $historyArray[] = [
                            'changes' => json_decode($history->changes),
                            'created_at' => $history->created_at->diffInHours() < 24
                                ? $history->created_at->diffForHumans()
                                : $history->created_at->format('d.m.Y, H:i'),
                        ];
                    }
                }
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by,
                    'history' => $historyArray,
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
        }
    }

    public function setOnRead(Request $request): void
    {
        $user = User::find(Auth::id());
        $wantedNotification = $user->notifications->find($request->notificationId);
        $wantedNotification->read_at = now();
        $wantedNotification->save();
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function updateSetting(Request $request, NotificationSetting $setting): void
    {
        if (Auth::id() !== $setting->user_id) {
            abort(403);
        }

        $setting->update($request->only("enabled_email", "frequency", "enabled_push"));
    }

    public function toggleGroup(Request $request): void
    {
        Auth::user()->notificationSettings()
            ->where('group_type', $request->groupType)
            ->update($request->only('enabled_email', 'enabled_push'));
    }

    public function destroy(string $id): string
    {
        $user = User::find(Auth::id());
        $notification = $user->notifications->find($id);
        $notification->delete();
        return 'Notification deleted';
    }
}
