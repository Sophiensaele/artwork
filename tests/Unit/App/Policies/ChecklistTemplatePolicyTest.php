<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Policies\ChecklistTemplatePolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class ChecklistTemplatePolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new ChecklistTemplatePolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new ChecklistTemplatePolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new ChecklistTemplatePolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new ChecklistTemplatePolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $policy = new ChecklistTemplatePolicy();

        $this->assertTrue($policy->delete($user));
    }
}
