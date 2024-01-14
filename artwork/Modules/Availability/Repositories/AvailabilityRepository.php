<?php

namespace Artwork\Modules\Availability\Repositories;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\Available;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityRepository
{
    public function getByIdAndModel(int $id, string $available): Collection
    {
        return Availability::where('available_id', $id)->where('available_type', $available)->get();
    }

    public function getVacationWithinInterval(Available $available, Carbon $from, Carbon $until): Collection
    {
        return $available->availabilities()
            ->where('from', '<=', $from)->where('until', '>=', $until)
            ->get();
    }

    public function delete(Collection|Availability $availability): void
    {
        $availability->delete();
    }

    public function save(Availability $availability): Availability
    {
        $availability->save();
        return $availability;
    }
}
