<?php

namespace Tests\Feature\InvitationController;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\Department;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserInvitationAcceptTest extends TestCase
{
    use RefreshDatabase;

    public function testAbortsInvalidTokens()
    {
        Invitation::factory()->create(['email' => 'user@example.com']);

        $this->post('/users/invitations/accept', [
            'email' => 'user@example.com',
            'token' => 'invalidToken12345678',
            'password' => 'testpassword',
        ])->assertForbidden();
    }

    public function testAbortsMissingParameters()
    {
        $token = 'validToken0123456789';

        $invitation = Invitation::factory()
            ->withToken($token)
            ->create();

        $this->post('/users/invitations/accept', [
            'token' => $token,
            'email' => $invitation->email,
        ])->assertInvalid();

        $this->post('/users/invitations/accept', [
            'token' => $token,
            'password' => 'testpassword',
            'email' => $invitation->email,
        ])->assertInvalid();
    }

    public function testAbortsWeakPasswords()
    {
        $token = 'validToken0123456789';

        $invitation = Invitation::factory()
            ->withToken($token)
            ->create();

        $this->post('/users/invitations/accept', [
            'token' => $token,
            'email' => $invitation->email,
            'password' => 'weakpassword'
        ])->assertInvalid();
    }

    public function testUsersCanAcceptTtheInvitation()
    {
        $validPlainToken = 'validToken0123456789';
        Role::firstOrCreate(['name' => RoleNameEnum::USER]);
        Permission::firstOrCreate(['name' => PermissionNameEnum::SETTINGS_UPDATE]);

        $department = Department::factory()->create();

        $invitation = Invitation::factory()->create([
            'email' => 'user@example.com',
            'token' => Hash::make($validPlainToken),
            'role' => RoleNameEnum::USER,
            'permissions' => json_encode([PermissionNameEnum::SETTINGS_UPDATE])]);

        $department->invitations()->attach($invitation->id);
        $invitation->departments()->attach($department->id);

        $password = 'TesterTest_123?';

        $this->post('/users/invitations/accept', [
            'email' => 'user@example.com',
            'first_name' => 'Benjamin',
            'last_name' => 'Willems',
            'token' => $validPlainToken,
            'password' => $password,
            'phone_number' => '123456789123',
            'position' => 'Chef',
            'business' => 'DTH',
            'description' => 'I am Chef'
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Benjamin',
            'last_name' => 'Willems',
            'email' => $invitation->email,
        ]);

        $user = User::where('email', 'user@example.com')->first();

        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $user->id
        ]);

        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->hasRole(RoleNameEnum::USER));
        $this->assertTrue($user->can(PermissionNameEnum::SETTINGS_UPDATE));
        $this->assertModelMissing($invitation);
        $this->assertAuthenticated();
    }
}