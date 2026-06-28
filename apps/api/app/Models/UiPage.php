<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiPage extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'ui_pages';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key',
        'page_key', 'name', 'description', 'page_type', 'status', 'visibility',
        'route_path', 'icon', 'layout_json', 'regions_json', 'components_json',
        'actions_json', 'conditions_json', 'breakpoints_json', 'theme_json', 'metadata',
        'created_by_user_id', 'created_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'layout_json' => 'array', 'regions_json' => 'array', 'components_json' => 'array',
            'actions_json' => 'array', 'conditions_json' => 'array', 'breakpoints_json' => 'array',
            'theme_json' => 'array', 'metadata' => 'array',
        ];
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UiActivityLog::class, 'ui_page_id');
    }
}
