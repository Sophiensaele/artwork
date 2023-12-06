<?php

namespace Artwork\Modules\Checklist\Models;

use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\BelongsToProject;
use Artwork\Modules\User\Models\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $project_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Task[] $tasks
 * @property-read Project $project
 * @property-read User $user
 * @property Collection|Department[]|array $departments
 */
class Checklist extends Model
{
    use BelongsToProject;
    use BelongsToUser;
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'user_id'
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}