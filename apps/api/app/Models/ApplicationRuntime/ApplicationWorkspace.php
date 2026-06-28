<?php

namespace App\Models\ApplicationRuntime;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationWorkspace extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'application_workspaces';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'application_runtime_app_id',
        'workspace_key',
        'name',
        'status',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationRuntimeApp::class, 'application_runtime_app_id');
    }
}
