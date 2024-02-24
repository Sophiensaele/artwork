<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Modules\User\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property string $commentable_type
 * @property string $commentable_id
 * @property string $comment
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 */
class SumComment extends Model
{
    use HasFactory;
    use BelongsToUser;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'comment',
        'user_id'
    ];
}
