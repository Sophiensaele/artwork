<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetShift;
use Illuminate\Http\Request;

class PresetShiftController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     */
    public function show(PresetShift $presetShift): void
    {
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     *
     */
    public function edit(PresetShift $presetShift): void
    {
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     */
    public function update(Request $request, PresetShift $presetShift): void
    {
        $presetShift->update(
            $request->only(
                [
                    'start',
                    'end',
                    'break_minutes',
                    'craft_id',
                    'number_employees',
                    'number_masters',
                    'description'
                ]
            )
        );
    }

    public function destroy(PresetShift $presetShift): void
    {
        $presetShift->delete();
    }
}
