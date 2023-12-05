<?php

namespace Tests\Feature;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChecklistControllerTest extends TestCase
{
    public function testChecklistUpdateAuthorization(): void
    {
        $checklist = Checklist::factory()->create();
        /** @var Department $department */
        $department = Department::factory()->create();

        // assert unauthenticated
        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]))->assertUnauthorized();

        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // user not authorized
        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [])
            ->assertForbidden();

        $user->departments()->sync([$department->id]);

        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
            'assigned_department_ids' => [$department->id]
        ])->assertForbidden();

        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [])
            ->assertFound();
    }

    public function testChecklistUpdateChecklist(): void
    {
        $checklist = Checklist::factory()->create();

        $this->actingAs($this->adminUser())
            ->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
                'name' => 'New Name',
            ])->assertRedirect();

        $this->assertDatabaseHas('checklists', [
            'name' => 'New Name',
        ]);
    }

    public function testChecklistUpdateTasks(): void
    {
        $checklist = Checklist::factory()->create();

        $this->actingAs($this->adminUser())
            ->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
                'tasks' => [
                    [
                        'name' => 'Some Name',
                        'done' => false,
                        'order' => 2,
                    ]
                ],
            ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Some Name',
            'checklist_id' => $checklist->id,
        ]);
    }
}
