<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowPackageHistory extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'workflow_package_history';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_package_id',
        'workflow_package_install_id',
        'action',
        'before_state',
        'after_state',
        'created_by_user_id',
        'created_by_membership_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function workflowPackage(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackage::class);
    }

    public function workflowPackageInstall(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackageInstall::class);
    }
}
