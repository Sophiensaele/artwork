<?php

namespace App\Http\Resources;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'profile_image' => $this->profile_image,
            'provider_name' => $this->provider_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'location' => $this->location,
            'can_master' => $this->can_master,
            'note' => $this->note,
            'salary_per_hour' => $this->salary_per_hour,
            'salary_description' => $this->salary_description,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'can_work_shifts' => $this->can_work_shifts,
            'assignedCrafts' => $this->assigned_crafts,
            'assignableCrafts' => Craft::query()->get()->filter(
                fn($craft) => !$this->assigned_crafts->pluck('id')->contains($craft->id)
            )->toArray()
        ];
    }
}