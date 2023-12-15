<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\Craft;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/ShiftSettings', [
            'crafts' => Craft::all(),
            'eventTypes' => EventType::all(),
            'usersWithPermission' => User::permission(PermissionNameEnum::SHIFT_PLANNER->value)->get(),
        ]);
    }

    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function edit(int $id): void
    {
    }

    public function update(Request $request, int $id): void
    {
    }

    public function destroy(int $id): void
    {
    }
}
