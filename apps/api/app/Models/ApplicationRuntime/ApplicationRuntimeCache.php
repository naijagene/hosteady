<?php

namespace App\Models\ApplicationRuntime;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApplicationRuntimeCache extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'application_runtime_cache';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'cache_key',
        'payload_json',
        'expires_at',
        'metadata',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
