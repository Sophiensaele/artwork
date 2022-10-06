<?php

namespace App\Models;

use App\Builders\EventBuilder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 *
 * @property int $id
 * @property ?string $name
 * @property ?string $description
 * @property ?Carbon $start_time
 * @property ?Carbon $end_time
 * @property ?bool $occupancy_option
 * @property ?bool $audience
 * @property ?bool $is_loud
 * @property ?int $event_type_id
 * @property ?int $room_id
 * @property int $user_id
 * @property ?int $project_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property EventType $event_type
 * @property Room $room
 * @property Project $project
 * @property User $creator
 * @property \Illuminate\Database\Eloquent\Collection<Event> $sameRoomEvents
 */
class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'is_loud' => 'boolean',
        'audience' => 'boolean',
        'occupancy_option' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function event_type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roomAdministrators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'user_id', 'room_id', 'room_id', 'id');
    }

    public function sameRoomEvents()
    {
        return $this->hasMany(Event::class, 'room_id', 'room_id');
    }

    /**
     * @return \App\Builders\EventBuilder<\App\Models\Event>
     */
    public static function query(): EventBuilder
    {
        return parent::query();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\EventBuilder
     */
    public function newEloquentBuilder($query): EventBuilder
    {
        return new EventBuilder($query);
    }

    public function getMinutesFromDayStart(Carbon $date): int
    {
        $startOfDay = $date->startOfDay()->subHours(2);

        if (Carbon::parse($this->start_time)->isBefore($startOfDay)) {
            return 1;
        }

        if ($startOfDay->diffInMinutes(Carbon::parse($this->start_time)) < 1440) {
            return $startOfDay->diffInMinutes(Carbon::parse($this->start_time));
        }

        return 1;
    }

    public function occursAtTime(Carbon $dateTime, bool $precisionDateTime = true): bool
    {
        // occurs on same day
        if (! $precisionDateTime) {
            return collect(CarbonPeriod::create($this->start_time, $this->end_time))
                ->contains(fn (Carbon $day) => $day->isSameDay($dateTime));
        }

        // occurs at same second
        return $this->start_time->lessThanOrEqualTo($dateTime) && $this->end_time->greaterThanOrEqualTo($dateTime);
    }

    public function conflictsWithAny(Collection $events): bool
    {
        return $events->unique()
            ->contains(fn (Event $event) => $this->conflictsWith($event));
    }

    public function conflictsWith(Event $event): bool
    {
        if ($event->id === $this->id) {
            return false;
        }

        return $this->start_time->isBetween($event->start_time, $event->end_time)
            || $this->end_time->isBetween($event->start_time, $event->end_time);
    }
}