<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Policies\TaskTemplatePolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class TaskTemplatePolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new TaskTemplatePolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new TaskTemplatePolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new TaskTemplatePolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new TaskTemplatePolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new TaskTemplatePolicy();

        $this->assertTrue($policy->delete($user));
    }
}
