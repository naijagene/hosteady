<?php

namespace App\Models;

use App\Enums\ScheduledTaskStatus;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledTask extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'module_key',
        'task_type',
        'display_name',
        'description',
        'cron_expression',
        'run_at',
        'timezone',
        'payload',
        'entity_reference',
        'status',
        'enabled',
        'last_run_at',
        'next_run_at',
        'created_by_user_id',
        'created_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'entity_reference' => 'array',
            'status' => ScheduledTaskStatus::class,
            'enabled' => 'boolean',
            'run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<ScheduledTaskRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ScheduledTaskRun::class);
    }
}
