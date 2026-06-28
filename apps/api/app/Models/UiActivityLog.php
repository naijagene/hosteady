<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UiActivityLog extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'ui_activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'ui_page_id', 'ui_component_id',
        'action', 'before_state', 'after_state', 'actor_user_id', 'actor_membership_id',
        'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array', 'after_state' => 'array', 'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(UiPage::class, 'ui_page_id');
    }
}
