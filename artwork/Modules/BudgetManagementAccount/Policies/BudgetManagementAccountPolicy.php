<?php

namespace Artwork\Modules\BudgetManagementAccount\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\User\Models\User;

class BudgetManagementAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(
            [
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }

    public function create(User $user): bool
    {
        return $user->canAny(
            [
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }

    public function delete(User $user): bool
    {
        return $user->canAny(
            [
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value
            ]
        );
    }
}
