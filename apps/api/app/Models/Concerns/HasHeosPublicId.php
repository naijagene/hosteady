<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasHeosPublicId
{
    protected static function bootHasHeosPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid7();
            }
        });
    }
}
