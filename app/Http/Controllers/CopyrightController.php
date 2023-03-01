<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use Illuminate\Http\Request;

class CopyrightController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $collecting_society = (object) $request->collectingSociety;

        Copyright::create([
            'own_copyright' => $request->ownCopyright,
            'live_music' => $request->liveMusic,
            'collecting_society_id' => $collecting_society->id,
            'law_size' => $request->lawSize,
            'project_id' => $request->project_id,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Copyright  $copyright
     * @return \Illuminate\Http\Response
     */
    public function show(Copyright $copyright)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Copyright  $copyright
     * @return \Illuminate\Http\Response
     */
    public function edit(Copyright $copyright)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Copyright  $copyright
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Copyright $copyright)
    {

        $collecting_society = (object) $request->collectingSociety;

        Copyright::where('id', $copyright->id)->update([
            'own_copyright' => $request->ownCopyright,
            'live_music' => $request->liveMusic,
            'collecting_society_id' => $collecting_society->id,
            'law_size' => $request->lawSize,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Copyright  $copyright
     * @return \Illuminate\Http\Response
     */
    public function destroy(Copyright $copyright)
    {
        //
    }
}