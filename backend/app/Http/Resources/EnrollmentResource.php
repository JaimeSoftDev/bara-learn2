<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Enrollment $resource
 */
class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'price_paid' => round($this->price_paid_cents / 100, 2),
            'enrolled_at' => $this->enrolled_at,
            'progress_percent' => $this->when(isset($this->progress_percent), $this->progress_percent),
            'course' => $this->whenLoaded('course', fn () => new CourseSummaryResource($this->course)),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'granted_by' => $this->whenLoaded('grantedBy', fn () => $this->grantedBy ? [
                'id' => $this->grantedBy->id,
                'name' => $this->grantedBy->name,
            ] : null),
        ];
    }
}
