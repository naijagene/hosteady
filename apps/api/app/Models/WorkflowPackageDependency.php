<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowPackageDependency extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_package_id',
        'dependency_key',
        'dependency_type',
        'version_constraint',
        'required',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function workflowPackage(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackage::class);
    }
}
