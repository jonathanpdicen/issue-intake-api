<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Issue
 */
class IssueResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'category' => $this->category,
            'status' => $this->status->value,
            'summary' => $this->summary,
            'suggested_next_action' => $this->suggested_next_action,
            'summary_status' => $this->summary_status->value,
            'needs_attention' => $this->needs_attention,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
