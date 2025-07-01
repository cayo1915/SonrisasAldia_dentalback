<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class UserCredentialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $role = $this->relationLoaded('role') ? $this->whenLoaded('role') : null;
        $rrhh = $this->relationLoaded('rrhh') ? $this->whenLoaded('rrhh') : null;
        return [
            'id'    => $this->id,
            'iduser' => $this->iduser,
            'email'          => $this->email,
            'user_name'      => $this->user_name,
            'n_document'     => $this->n_document,
            'ps_w'            => Crypt::decryptString($this->encrypted_password),
            'created_at' => isset($this->created_at) ? Carbon::parse($this->created_at)->format('d M Y, h:i a') : null,
            'updated_at' => isset($this->updated_at) ? Carbon::parse($this->updated_at)->format('d M Y, h:i a') : null,
            'status'     => $this->status == '1' ? 'Active' : 'Inactive',
            'idrole'    =>  $this->idrole,
            $this->mergeWhen($role, fn() => [
                'role'    => $this->role->name,
            ]),
            'idrrhh'    =>  $this->idrrhh,
            $this->mergeWhen($rrhh, fn() => [
                'name'    => $rrhh->name,
                'surname'    => $rrhh->surname,
                'second_surname'    => $rrhh->second_surname,
                'birth_date'    => $rrhh->birth_date,
                'direccion'    => $rrhh->direccion,
                'birth_date'    => $rrhh->birth_date,
                'idcharge'      => $rrhh->idcharge,
            ]),
        ];
    }
}
