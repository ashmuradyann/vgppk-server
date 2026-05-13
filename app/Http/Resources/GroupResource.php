<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'academic_year' => $this->academic_year,
            'teacher_name' => $this->teacher_name,
            'practise_type' => $this->practise_type,
            'specialty' => new SpecialtyResource($this->whenLoaded('specialty')),
            'students' => StudentResource::collection($this->whenLoaded('students')),
        ];
    }
}
