<?php

namespace App\Models\ApplicationRuntime;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationNavigation extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'application_navigation';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'application_runtime_app_id',
        'menu_key',
        'navigation_key',
        'label',
        'item_type',
        'parent_key',
        'sort_order',
        'route_json',
        'badge_json',
        'required_permission',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'route_json' => 'array',
            'badge_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationRuntimeApp::class, 'application_runtime_app_id');
    }
}
