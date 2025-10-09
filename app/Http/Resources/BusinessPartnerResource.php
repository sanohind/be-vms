<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Gabungkan alamat dari beberapa kolom
        $addressParts = array_filter([
            $this->adr_line_1,
            $this->adr_line_2,
            $this->adr_line_3,
            $this->adr_line_4
        ]);
        $fullAddress = implode(', ', $addressParts);

        return [
            'bp_code' => $this->bp_code,
            'parent_bp_code' => $this->parent_bp_code ?? null,
            'bp_name' => $this->bp_name,
            'bp_address' => $fullAddress,
            'bp_phone' => $this->bp_phone ?? null,
            'bp_fax' => $this->bp_fax ?? null,
            'bp_role' => $this->bp_role ?? null,
            'bp_role_desc' => $this->bp_role_desc ?? null,
            'bp_status_desc' => $this->bp_status_desc ?? null,
            'bp_currency' => $this->bp_currency ?? null,
            'country' => $this->country ?? null,
            'status' => $this->status,
            'base_bp_code' => $this->base_bp_code,
            'is_parent' => $this->isParentRecord(),
            'is_child' => $this->isChildRecord(),
            'parent_record' => $this->when($this->isChildRecord(), function () {
                return $this->getParentRecord();
            }),
            'child_records' => $this->when($this->isParentRecord(), function () {
                return $this->getChildRecords();
            }),
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
        ];
    }
}
