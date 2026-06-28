<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportExport extends Model
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
        'report_run_id',
        'export_format',
        'status',
        'file_reference',
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
            'export_format' => 'string',
            'status' => 'string',
            'file_reference' => 'array',
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

    public function reportRun(): BelongsTo
    {
        return $this->belongsTo(ReportRun::class);
    }
}
