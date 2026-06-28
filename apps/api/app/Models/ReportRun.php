<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportRun extends Model
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
        'report_definition_id',
        'status',
        'parameters_json',
        'result_json',
        'started_at',
        'completed_at',
        'duration_ms',
        'created_by_user_id',
        'created_by_membership_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'parameters_json' => 'array',
            'result_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
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

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
