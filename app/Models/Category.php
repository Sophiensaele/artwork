<?php

namespace App\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int	$id
 * @property string	$name
 * @property \Carbon\Carbon	$created_at
 * @property \Carbon\Carbon	$updated_at
 * @property \Illuminate\Support\Collection<\Artwork\Modules\Project\Models\Project> $projects
 * @property string $deleted_at
 */
class Category extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'name'
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}


