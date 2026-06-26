<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Human\Data\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskComment */
class TaskCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TaskComment $comment */
        $comment = $this->resource;

        return $comment->toArray();
    }
}
