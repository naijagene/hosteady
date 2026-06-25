<?php

namespace App\Models;

use App\Enums\WorkspaceSettingType;
use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceApplicationSetting extends Model
{
    use HasHeosAudit, HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workspace_application_id',
        'setting_key',
        'setting_value',
        'setting_type',
        'version',
        'is_sensitive',
        'is_encrypted',
        'created_by_user_id',
        'updated_by_user_id',
        'deleted_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'setting_value' => 'json',
            'setting_type' => WorkspaceSettingType::class,
            'is_sensitive' => 'boolean',
            'is_encrypted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(WorkspaceApplication::class);
    }

    /**
     * @return HasMany<WorkspaceApplicationSettingHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(WorkspaceApplicationSettingHistory::class);
    }
}
