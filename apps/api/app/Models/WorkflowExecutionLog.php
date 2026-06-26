<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecutionLog extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_instance_id',
        'workflow_execution_step_id',
        'level',
        'message',
        'context',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecutionStep::class, 'workflow_execution_step_id');
    }
}
