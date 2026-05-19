<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->uuid,
            'name'       => $this->name,
            'email'      => $this->email,
            'is_active'  => (bool) $this->is_active,
            'roles'      => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            // All role and permission values (loaded on show)
            'all_permissions' => $this->when(
                $this->relationLoaded('roles') || $this->relationLoaded('permissions'),
                fn() => $this->getAllPermissions()->pluck('name')
            ),

            'employee' => $this->whenLoaded('employee', fn() => $this->employee ? [
                'id'              => $this->employee->uuid,
                'employee_number' => $this->employee->employee_number,
                'full_name'       => $this->employee->full_name,
                'position'        => $this->employee->position,
            ] : null),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // password and remember_token never exposed
        ];
    }
}
