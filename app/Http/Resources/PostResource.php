<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            ...collect($this->getFillable())->mapWithKeys(fn($f) => [$f => $this->{$f}])->all(),
            'created_at' => $this->created_at,
        ];
    }
}
