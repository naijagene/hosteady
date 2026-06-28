<?php

namespace App\Http\Resources;

use App\Models\PlatformNotification;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NotificationReference) {
            return [
                'public_id' => $this->resource->publicId,
                'title' => $this->resource->title,
                'body' => $this->resource->body,
                'status' => $this->resource->status,
                'priority' => $this->resource->priority,
                'scope' => $this->resource->scope,
                'channels' => $this->resource->channels,
                'read_at' => $this->resource->readAt,
                'created_at' => $this->resource->createdAt,
            ];
        }

        if ($this->resource instanceof PlatformNotification) {
            return [
                'public_id' => $this->resource->public_id,
                'title' => $this->resource->title,
                'body' => $this->resource->body,
                'status' => $this->resource->status->value,
                'priority' => 'normal',
                'scope' => 'user',
                'channels' => $this->resource->channel !== null && $this->resource->channel !== ''
                    ? [$this->resource->channel]
                    : [],
                'read_at' => $this->resource->read_at?->toIso8601String(),
                'created_at' => $this->resource->created_at?->toIso8601String(),
            ];
        }

        return [
            'public_id' => data_get($this->resource, 'public_id'),
            'title' => data_get($this->resource, 'title'),
            'body' => data_get($this->resource, 'body'),
            'status' => data_get($this->resource, 'status'),
            'priority' => data_get($this->resource, 'priority', 'normal'),
            'scope' => data_get($this->resource, 'scope', 'user'),
            'channels' => data_get($this->resource, 'channels', []),
            'read_at' => data_get($this->resource, 'read_at'),
            'created_at' => data_get($this->resource, 'created_at'),
        ];
    }
}
