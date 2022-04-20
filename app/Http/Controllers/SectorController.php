<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class SectorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Sector::class);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Sector::create([
            'name' => $request->name,
        ]);
        return Redirect::back()->with('success', 'Sector created');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sector  $sector
     */
    public function update(Request $request, Sector $sector)
    {
        $sector->update($request->only('name'));

        /*
        if (Auth::user()->can('update projects')) {
            $sector->projects()->sync(
                collect($request->assigned_project_ids)
                    ->map(function ($project_id) {
                        return $project_id;
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign projects to a sector.'], 403);
        }
        */
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sector  $sector
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Sector $sector)
    {
        $sector->delete();
        return Redirect::back()->with('success', 'Sector deleted');
    }
}
