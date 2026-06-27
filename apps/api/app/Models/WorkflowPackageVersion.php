<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowPackageVersion extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_package_id',
        'version',
        'manifest_json',
        'package_json',
        'checksum',
        'status',
        'published_at',
        'deprecated_at',
        'created_by_user_id',
        'created_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manifest_json' => 'array',
            'package_json' => 'array',
            'status' => WorkflowPackageStatus::class,
            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
        ];
    }

    public function workflowPackage(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackage::class);
    }
}
