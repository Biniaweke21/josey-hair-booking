<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'read_at' => $this->read_at,
            'sent_at' => $this->sent_at,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
            'booking' => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
