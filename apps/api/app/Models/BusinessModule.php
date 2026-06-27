<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Development\Enums\BusinessModuleStatus;
use App\Modules\Sdk\Development\Enums\BusinessModuleType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessModule extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'module_key',
        'name',
        'description',
        'type',
        'status',
        'version',
        'manifest_json',
        'capabilities',
        'permissions',
        'routes',
        'dependencies',
        'metadata',
        'installed_at',
        'enabled_at',
        'disabled_at',
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
            'capabilities' => 'array',
            'permissions' => 'array',
            'routes' => 'array',
            'dependencies' => 'array',
            'metadata' => 'array',
            'status' => BusinessModuleStatus::class,
            'type' => BusinessModuleType::class,
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function installations(): HasMany
    {
        return $this->hasMany(BusinessModuleInstallation::class);
    }
}
