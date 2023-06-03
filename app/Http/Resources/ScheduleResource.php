<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => (string)$this->id,
            'attributes' => [
                'title' => $this->title,
                'scheduled_at' => $this->scheduled_at,
                'duration' => $this->duration,
                'start_at' => $this->start_at,
                'contact_id' => $this->contact_id,
                'contact_group_id' => $this->contact_group_id,
                'status' => $this->status,
                'message_id' => $this->message_id,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' =>[
                'user' => [
                    'id' => (string)$this->user_id,
                    'user name' => $this->name,
                    'user email' => $this->email,
                    'created_at' => $this->user_created_at,
                ],
                // 'message' => [

                // ],
            ]
        ];
    }
}
