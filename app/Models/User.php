<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon $email_verified_at
 * @property string $phone_number
 * @property string $password
 * @property string $two_factor_secret
 * @property string $two_factor_recovery_codes
 * @property string $position
 * @property string $business
 * @property string $description
 * @property string $toggle_hints
 * @property string $remember_token
 * @property int $current_team_id
 * @property string $profile_photo_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property boolean $project_management
 *
 * @property Collection<\App\Models\Department> departments
 * @property Collection<\App\Models\Project> projects
 * @property Collection<\App\Models\Comment> comments
 * @property Collection<\App\Models\Checklist> private_checklists
 * @property Collection<\App\Models\Room> created_rooms
 * @property Collection<\App\Models\Room> admin_rooms
 * @property Collection<\App\Models\Task> done_tasks
 * @property Collection<\App\Models\Event> events
 * @property Collection<\App\Models\Task> $privateTasks
 *
 * What is this sorcery?
 * @property string $profile_photo_url
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'position',
        'business',
        'description',
        'toggle_hints',
        'opened_checklists',
        'opened_areas'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'opened_checklists' => 'array',
        'opened_areas' => 'array',
        'toggle_hints' => 'boolean'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function project_files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function notificationSettings(): HasMany {
        return $this->hasMany(NotificationSetting::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withPivot('access_budget', 'is_manager', 'can_write');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function private_checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function created_rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function admin_rooms()
    {
        return $this->belongsToMany(Room::class, 'room_user');
    }

    public function done_tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function privateTasks()
    {
        return $this->hasManyThrough(Task::class, Checklist::class);
    }

    public function getPermissionAttribute()
    {
        return $this->getAllPermissions();
    }

    public function globalNotifications()
    {
        return $this->hasOne(GlobalNotification::class, 'created_by');
    }

    public function money_sources(){
        return $this->hasMany(MoneySource::class, 'creator_id');
    }

    public function money_source_tasks(): HasMany
    {
        return $this->hasMany(MoneySourceTask::class, 'user_id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name
        ];
    }
}
