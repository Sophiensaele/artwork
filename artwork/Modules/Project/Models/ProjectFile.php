<?php

namespace Artwork\Modules\Project\Models;

use App\Models\Comment;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Trails\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $project_id
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class ProjectFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToProject;

    protected $guarded = [
        'id'
    ];

    public function accessingUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}