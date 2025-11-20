<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'visitor_id' => $this->visitor_id,
            'visitor_date' => Carbon::parse($this->visitor_date)->format('Y-m-d'),
            'visitor_name' => $this->visitor_name,
            'visitor_from' => $this->visitor_from,
            'visitor_host' => $this->visitor_host,
            'visitor_needs' => $this->visitor_needs,
            'visitor_amount' => $this->visitor_amount,
            'visitor_vehicle' => $this->visitor_vehicle,
            'plan_delivery_time' => $this->plan_delivery_time,
            'department' => $this->department,
            // 'visitor_img' => $this->visitor_img,

            'visitor_checkin' => Carbon::Parse($this->visitor_checkin)->format('d-m-Y H:i'),
            'visitor_checkout' => $this->visitor_checkout ? Carbon::parse($this->visitor_checkout)->format('d-m-Y H:i') : null,
        ];
    }
}
