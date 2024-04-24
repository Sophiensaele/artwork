<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ServiceProviderShowResource;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateServiceProviderShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Repositories\ShiftQualificationRepository;
use Artwork\Modules\ShiftQualification\Services\ServiceProviderShiftQualificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProviderController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $serviceProvider = ServiceProvider::create(
            ['profile_image' => 'https://ui-avatars.com/api/?name=NEU&color=7F9CF5&background=EBF4FF']
        );

        return Inertia::location(route('service_provider.show', $serviceProvider->id));
    }

    public function show(
        ServiceProvider $serviceProvider,
        CalendarController $shiftPlan,
        ShiftQualificationRepository $shiftQualificationRepository
    ): Response {
        $showCalendar = $shiftPlan->createCalendarDataForServiceProviderShiftPlan($serviceProvider);

        return Inertia::render('ServiceProvider/Show', [
            'serviceProvider' => new ServiceProviderShowResource($serviceProvider),
            'dateValue' => $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $serviceProvider
                ->shifts()
                ->with(['event', 'event.project', 'event.room'])
                ->orderBy('start', 'ASC')
                ->get(),
            'shiftQualifications' => $shiftQualificationRepository->getAllAvailableOrderedByCreationDateAscending()
        ]);
    }

    public function edit(ServiceProvider $serviceProvider): void
    {
    }

    public function update(Request $request, ServiceProvider $serviceProvider): void
    {
        $serviceProvider->update($request->only([
            'provider_name',
            'email',
            'phone_number',
            'street',
            'zip_code',
            'location',
            'note',
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateTerms(ServiceProvider $serviceProvider, Request $request): void
    {
        $this->authorize('updateTerms', ServiceProvider::class);

        $serviceProvider->update($request->only([
            'salary_per_hour',
            'salary_description',
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateWorkProfile(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateCraftSettings(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateShiftQualification(
        ServiceProvider $serviceProvider,
        UpdateServiceProviderShiftQualificationRequest $request,
        ServiceProviderShiftQualificationService $serviceProviderShiftQualificationService
    ): RedirectResponse {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        if ($request->boolean('create')) {
            //if useable is set to true create a new entry in pivot table
            $serviceProviderShiftQualificationService->createByRequestForServiceProvider($request, $serviceProvider);
        } else {
            //if useable is set to false pivot table entry needs to be deleted
            $serviceProviderShiftQualificationService->deleteByRequestForServiceProvider($request, $serviceProvider);
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function assignCraft(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$serviceProvider->assignedCrafts->contains($craftToAssign)) {
            $serviceProvider->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function removeCraft(ServiceProvider $serviceProvider, Craft $craft): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->assignedCrafts()->detach($craft);

        return Redirect::back();
    }

    public function destroy(ServiceProvider $serviceProvider): RedirectResponse
    {
        $serviceProvider->delete();

        return Redirect::back();
    }

    public function updateProfileImage(Request $request, ServiceProvider $serviceProvider): void
    {
        if (!Storage::exists("public/profile-photos")) {
            Storage::makeDirectory("public/profile-photos");
        }

        $file = $request->file('profileImage');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('public/profile-photos', $file, $basename);

        $serviceProvider->update(['profile_image' => Storage::url('public/profile-photos/' . $basename)]);
    }
}
