<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiComponent extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'ui_components';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key',
        'component_key', 'name', 'description', 'component_type', 'status',
        'binding_type', 'binding_config', 'actions_json', 'conditions_json', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'binding_config' => 'array', 'actions_json' => 'array',
            'conditions_json' => 'array', 'metadata' => 'array',
        ];
    }
}
