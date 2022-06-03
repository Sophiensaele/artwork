<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RoomController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'temporary' => $request->temporary,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'area_id' => $request->area_id,
            'user_id' => $request->user_id,
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room)
    {
        $events = [];
        if($room->room_admins()->contains(Auth::id())) {
            $events = $room->events;
        }
        return inertia('Rooms/Show', [
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'temporary' => $room->temporary,
                'created_by' => User::where('id', $room->user_id)->first(),
                'created_at' => Carbon::parse($room->created_at)->format('d.m.Y'),
                'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                'room_files' => $room->room_files,
                'room_admins' => $room->room_admins->map(fn($room_admin) => [
                    'id' => $room_admin->id,
                    'first_name' => $room_admin->first_name,
                    'last_name' => $room_admin->last_name,
                    'email' => $room_admin->email,
                    'profile_photo_url' => $room_admin->profile_photo_url
                ]),
                'room_events' => $events,
                'area' => $room->area
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Room $room)
    {
        $room->update($request->only('name', 'description', 'temporary', 'start_date', 'end_date'));

        $room->room_admins()->sync(
            collect($request->room_admins)
                ->map(function ($room_admin) {

                    $this->authorize('update', User::find($room_admin['id']));

                    return $room_admin['id'];
                })
        );

        return Redirect::back()->with('success', 'Room updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Room $room) {

        $new_room = Room::create([
            'name' => '(Kopie) ' . $room->name,
            'description' => $room->description,
            'temporary' => $room->temporary,
            'start_date' => $room->start_date,
            'end_date' => $room->end_date,
            'area_id' => $room->area_id,
            'user_id' => Auth::id(),
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('rooms.show', $new_room->id)->with('success', 'Room created.');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Room $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request)
    {

        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    public function getTrashed()
    {
        return inertia('Trash/Rooms', [
            'trashed_rooms' => Area::paginate(10)->through(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->trashed_rooms->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => (bool)$room->temporary,
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ])
        ]);
    }

//    public function getRoomBelegung() {
//
//        $period = [ ['date' => Carbon::now()], [['date' => Carbon::now()]];
//
//        return inertia('Trash/Rooms', [
//            'rooms' => Room::all()->with('events')->get()->map(fn ($room) => [
//                'name' => $room->name,
//                'days' => $period->map(fn ($day) => [
//                    'weekday' => '',
//                    'date' => '20.05.2022',
//                    'events' => $room->events()->where('start_date', $day['date'])
//                ])
//            ])
//        ]);
//
//        room in rooms
//            room in rooms[0].days
//
//        room in  rooms
//           kachel in rooms.days
//             kachel.events
//
//    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Room $room)
    {
        $room->delete();
        return Redirect::route('areas.management')->with('success', 'Room moved to trash');
    }

    public function forceDelete(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();
    }

    public function restore(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->restore();
    }
}
