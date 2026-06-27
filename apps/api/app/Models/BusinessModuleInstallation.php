<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessModuleInstallation extends Model
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
        'business_module_id',
        'installed_version',
        'status',
        'settings',
        'installed_at',
        'enabled_at',
        'disabled_at',
        'installed_by_user_id',
        'installed_by_membership_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'metadata' => 'array',
            'status' => BusinessModuleInstallStatus::class,
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
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

    public function businessModule(): BelongsTo
    {
        return $this->belongsTo(BusinessModule::class);
    }
}
