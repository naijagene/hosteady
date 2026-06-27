<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableDefinition extends Model
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
        'module_key',
        'entity_key',
        'table_key',
        'name',
        'description',
        'type',
        'status',
        'visibility',
        'columns_json',
        'filters_json',
        'sorts_json',
        'default_sort_json',
        'pagination_json',
        'actions_json',
        'views_json',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'string',
            'status' => 'string',
            'visibility' => 'string',
            'columns_json' => 'array',
            'filters_json' => 'array',
            'sorts_json' => 'array',
            'default_sort_json' => 'array',
            'pagination_json' => 'array',
            'actions_json' => 'array',
            'views_json' => 'array',
            'metadata' => 'array',
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

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdByMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'created_by_membership_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(TableView::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TableActivityLog::class);
    }
}
