<?php

namespace Artwork\Modules\Permission\Models;

use Artwork\Core\Database\Models\InteractsWithDatabase;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $guard_name
 * @property string|null $name_de
 * @property string|null $group
 * @property string|null $tooltipText //why is this camelcase?
 * @property bool $checked
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Permission extends SpatiePermission implements InteractsWithDatabase
{

}
