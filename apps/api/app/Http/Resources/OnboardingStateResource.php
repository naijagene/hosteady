<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\OnboardingState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof OnboardingState) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
