<?php

namespace App\Models\ApplicationRuntime;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationRuntimeApp extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'application_runtime_apps';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'application_key',
        'name',
        'description',
        'application_type',
        'status',
        'visibility',
        'module_key',
        'catalog_application_id',
        'manifest_json',
        'metadata',
        'created_by_user_id',
        'created_membership_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'manifest_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(ApplicationWorkspace::class, 'application_runtime_app_id');
    }

    public function navigation(): HasMany
    {
        return $this->hasMany(ApplicationNavigation::class, 'application_runtime_app_id');
    }
}
