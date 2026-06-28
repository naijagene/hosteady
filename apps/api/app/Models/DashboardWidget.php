<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardWidget extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'dashboard_definition_id',
        'widget_key',
        'name',
        'description',
        'widget_type',
        'chart_type',
        'data_source_type',
        'data_source_config',
        'metric_json',
        'filters_json',
        'layout_json',
        'actions_json',
        'refresh_mode',
        'metadata',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'widget_type' => 'string',
            'chart_type' => 'string',
            'data_source_type' => 'string',
            'data_source_config' => 'array',
            'metric_json' => 'array',
            'filters_json' => 'array',
            'layout_json' => 'array',
            'actions_json' => 'array',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function dashboardDefinition(): BelongsTo
    {
        return $this->belongsTo(DashboardDefinition::class);
    }
}
