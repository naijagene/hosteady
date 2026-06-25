<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasHeosAudit, HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'module_uuid',
        'manifest_version',
        'key',
        'name',
        'description',
        'version',
        'status',
        'is_core',
        'icon',
        'category',
        'capabilities',
        'dependencies',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'is_core' => 'boolean',
            'capabilities' => 'array',
            'dependencies' => 'array',
        ];
    }

    public function settingDefinitions(): HasMany
    {
        return $this->hasMany(ApplicationSettingDefinition::class);
    }

    public function organizationApplications(): HasMany
    {
        return $this->hasMany(OrganizationApplication::class);
    }
}
