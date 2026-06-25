<?php

namespace App\Models;

use App\Enums\WorkspaceSettingChangeType;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceApplicationSettingHistory extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'workspace_application_setting_history';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workspace_application_setting_id',
        'workspace_application_id',
        'setting_key',
        'version',
        'change_type',
        'before_value',
        'after_value',
        'changed_by_user_id',
        'changed_by_membership_id',
        'reason',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_value' => 'json',
            'after_value' => 'json',
            'change_type' => WorkspaceSettingChangeType::class,
            'created_at' => 'datetime',
        ];
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(WorkspaceApplication::class);
    }

    public function workspaceApplicationSetting(): BelongsTo
    {
        return $this->belongsTo(WorkspaceApplicationSetting::class);
    }

    public function changedByMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'changed_by_membership_id');
    }
}
