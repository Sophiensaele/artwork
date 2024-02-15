<?php

namespace Artwork\Modules\Shift\Services;

use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Models\User;
use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;

class ShiftUserService
{
    use ReceivesNewHistoryServiceTrait;

    public function __construct(
        private readonly ShiftRepository $shiftRepository,
        private readonly ShiftUserRepository $shiftUserRepository,
        private readonly ShiftFreelancerRepository $shiftFreelancerRepository,
        private readonly ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private readonly ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private readonly ShiftCountService $shiftCountService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function assignToShift(
        Shift $shift,
        int $userId,
        int $shiftQualificationId,
        array|null $seriesShiftData = null
    ): void {
        $shiftUserPivot = $this->shiftUserRepository->createForShift(
            $shift->id,
            $userId,
            $shiftQualificationId
        );

        $this->shiftCountService->handleShiftUsersShiftCount($shift, $userId);

        /** @var User $user */
        $user = $shiftUserPivot->user;
        $this->assignUserToProjectIfNecessary($shift, $user);

        if ($shift->is_committed) {
            $this->handleAssignedToShift($shift, $user, $shiftUserPivot->shiftQualification);
        }

        if (
            $seriesShiftData !== null &&
            isset($seriesShiftData['onlyThisDay']) &&
            $seriesShiftData['onlyThisDay'] === false
        ) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $userId,
                $shiftQualificationId
            );
        }
    }

    private function handleAssignedToShift(Shift $shift, User $user, ShiftQualification $shiftQualification): void
    {
        $this->createAssignedToShiftHistoryEntry($shift, $user, $shiftQualification);
        $this->createAssignedToShiftNotification($shift, $user);
        if (
            $user->vacations()
                ->where('date', '<=', $shift->event_start_day)
                ->where('date', '>=', $shift->event_end_day)
                ->count() > 0
        ) {
            $this->createVacationConflictNotification($shift, $user);
        }
        $this->checkShortBreakAndCreateNotificationsIfNecessary($shift, $user);
        $this->checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary($shift, $user);
        $this->checkVacationConflicts($shift, $user);
        $this->checkAvailabilityConflicts($shift, $user);
    }

    private function assignUserToProjectIfNecessary(Shift $shift, User $user): void
    {
        $project = $shift->event->project;
        if (!$project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }
    }

    private function createAssignedToShiftHistoryEntry(
        Shift $shift,
        User $user,
        ShiftQualification $shiftQualification
    ): void {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde zur Schicht (' .
                $shift->craft->abbreviation . ' - ' . $shift->event->eventName . ') als "' .
                $shiftQualification->name . '" hinzugefügt',
            'shift'
        );
    }

    private function createAssignedToShiftNotification(Shift $shift, User $user): void
    {
        $this->notificationService->setProjectId($shift->event->project->id);
        $this->notificationService->setEventId($shift->event->id);
        $this->notificationService->setShiftId($shift->id);
        $notificationTitle = 'Neue Schichtbesetzung ' .
            $shift->event->project->name . ' ' . $shift->craft->abbreviation;
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage([
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $this->notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => 'Deine Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ]);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    private function createVacationConflictNotification(Shift $shift, User $user): void
    {
        $notificationTitle = 'Schichtkonflikt ' . Carbon::parse($shift->event_start_day)->format('d.m.Y') .
            ' ' . $shift->event->project->name . ' ' . $shift->craft->abbreviation;
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService
            ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
        $this->notificationService->setBroadcastMessage([
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $this->notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => $user->getFullNameAttribute() . ' ist nicht verfügbar',
                'href' => null
            ],
        ]);
        $this->notificationService->setButtons(['change_shift_conflict']);
        $usersWhichGotNotification = [];
        foreach ($user->crafts as $craft) {
            foreach ($craft->users as $craftUser) {
                if (in_array($craftUser->id, $usersWhichGotNotification)) {
                    continue;
                }
                $this->notificationService->setNotificationTo($craftUser);
                $this->notificationService->createNotification();
                $usersWhichGotNotification[] = $craftUser->id;
            }
        }
        $this->notificationService->clearNotificationData();
    }

    private function checkShortBreakAndCreateNotificationsIfNecessary(Shift $shift, User $user): void
    {
        $shiftBreakCheck = $this->notificationService->checkIfShortBreakBetweenTwoShifts($user, $shift);

        if ($shiftBreakCheck->shortBreak) {
            $notificationTitle = 'Du wurdest mit zu kurzer Ruhepause geplant';
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage([
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ]);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin
            $notificationTitle = 'Mitarbeiter*in mit zu kurzer Ruhepause geplant';
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
                $this->notificationService->setNotificationTo($adminUser);
                $this->notificationService->createNotification();
            }

            $usersWhichGotNotification = [];
            foreach ($user->crafts as $craft) {
                foreach ($craft->users as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    if (in_array($craftUser->id, $usersWhichGotNotification)) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                    $usersWhichGotNotification[] = $craftUser->id;
                }
            }
            $this->notificationService->clearNotificationData();
        }
    }

    private function checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary(Shift $shift, User $user): void
    {
        $shiftCheck = $this->notificationService->checkIfUserInMoreThanTenShifts($user, $shift);

        if ($shiftCheck->moreThanTenShifts) {
            $notificationTitle = 'Du wurdest mehr als 10 Tage am Stück eingeplant';
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage([
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y') .
                        ' - ' . Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ]);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin
            $notificationTitle = 'Mitarbeiter*in mehr als 10 Tage am Stück eingeplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y') .
                        ' - ' . Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
                $this->notificationService->setNotificationTo($adminUser);
                $this->notificationService->createNotification();
            }

            $usersWhichGotNotification = [];
            foreach ($user->crafts as $craft) {
                foreach ($craft->users as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    if (in_array($craftUser->id, $usersWhichGotNotification)) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                    $usersWhichGotNotification[] = $craftUser->id;
                }
            }
        }

        $this->notificationService->clearNotificationData();
    }

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $userId,
        int $shiftQualificationId
    ): void {
        /** @var Shift $shiftBetweenDates */
        foreach (
            $this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates
        ) {
            if (
                //same shift is found, user already assigned, continue
                $shiftBetweenDates->id === $shift->id ||
                //if day of week is given and is not "all" compare it to shift, if not matching continue
                (
                    $dayOfWeek !== 'all' &&
                    Carbon::parse($shiftBetweenDates->event_start_day)->dayOfWeek !== ((int) $dayOfWeek)
                ) ||
                //if user already assigned to shift continue
                $shiftBetweenDates->users()
                    ->get(['users.id'])
                    ->pluck('id')
                    ->contains($userId)
            ) {
                continue;
            }

            //get value of shifts qualifications by shiftQualificationId and shiftId to determine how many users
            //can be assigned in total
            $shiftsQualificationsValue = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
                $shiftBetweenDates->id,
                $shiftQualificationId
            )?->value;

            //if shiftsQualifications value is null or 0 continue
            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            //determine if a slot is available, get all shift_user, shifts_freelancers and shifts_service_providers
            //entries containing shiftId and shiftQualificationId and count them
            if (
                $this->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
                    $shiftBetweenDates->id,
                    $shiftQualificationId
                ) < $shiftsQualificationsValue
            ) {
                //call assignToShift without seriesShiftData to make sure only this user is assigned to shift and same
                //logic is applied for each user
                $this->assignToShift($shiftBetweenDates, $userId, $shiftQualificationId, null);
            }
        }
    }

    private function getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
        int $shiftId,
        int $shiftQualificationId
    ): int {
        return $this->shiftUserRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        ) + $this->shiftFreelancerRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        ) + $this->shiftServiceProviderRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );
    }

    public function removeFromShift(ShiftUser|int $usersPivot, bool $removeFromSingleShift): void
    {
        $shiftUserPivot = !$usersPivot instanceof ShiftUser ?
            $this->shiftUserRepository->getById($usersPivot) :
            $usersPivot;

        /** @var Shift $shift */
        $shift = $shiftUserPivot->shift;
        /** @var User $user */
        $user = $shiftUserPivot->user;

        $this->shiftUserRepository->delete($shiftUserPivot);

        $this->shiftCountService->handleShiftUsersShiftCount($shift, $user->id);

        if ($shift->is_committed) {
            $this->handleRemovedFromShift($shift, $user);
        }

        if (!$removeFromSingleShift) {
            foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
                if ($shiftByUuid->id === $shift->id) {
                    continue;
                }

                //find additional shift user pivot by shift and given user id, if found call this function again
                //with removeFromSingleShift set to true making sure same logic is applied for each pivot which is
                //deleted
                $shiftUserPivotByUuid = $this->shiftRepository->getShiftUserPivotById($shiftByUuid, $user->id);
                if ($shiftUserPivotByUuid instanceof ShiftUser) {
                    $this->removeFromShift($shiftUserPivotByUuid, true);
                }
            }
        }
    }

    public function removeAllUsersFromShift(Shift $shift): void
    {
        $shift->users()->each(
            function (User $user): void {
                //call remove from shift with removeFromSingleShift set to true making sure same logic is applied
                //for each pivot which is deleted
                $this->removeFromShift($user->pivot, true);
            }
        );
    }

    public function removeFromShiftByUserIdAndShiftId(int $userId, int $shiftId): void
    {
        $this->removeFromShift(
            $this->shiftUserRepository->findByUserIdAndShiftId(
                $userId,
                $shiftId
            ),
            true
        );
    }

    private function handleRemovedFromShift(Shift $shift, User $user): void
    {
        $this->createRemovedFromShiftHistoryEntry($shift, $user);
        $this->createRemovedFromShiftNotification($shift, $user);
        $this->checkVacationConflicts($shift, $user);
        $this->checkAvailabilityConflicts($shift, $user);
    }

    private function createRemovedFromShiftHistoryEntry(Shift $shift, User $user): void
    {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde von Schicht (' .
            $shift->craft->abbreviation . ' - ' . $shift->event->eventName . ') entfernt',
            'shift'
        );
    }

    private function createRemovedFromShiftNotification(Shift $shift, User $user): void
    {
        $this->notificationService->setProjectId($shift->event->project->id);
        $this->notificationService->setEventId($shift->event->id);
        $this->notificationService->setShiftId($shift->id);
        $notificationTitle = 'Schichtbesetzung gelöscht  ' .
            $shift->event->project->name . ' ' . $shift->craft->abbreviation;
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage([
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $this->notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => 'Betrifft Schicht: ' .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ]);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    private function checkVacationConflicts(Shift $shift, User $user): void
    {
        $this->vacationConflictService->checkVacationConflictsShifts($shift, $user);
    }

    private function checkAvailabilityConflicts(Shift $shift, User $user): void
    {
        $this->availabilityConflictService->checkAvailabilityConflictsShifts($shift, $user);
    }
}