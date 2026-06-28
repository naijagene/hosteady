<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiLayout extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'ui_layouts';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key',
        'layout_key', 'name', 'description', 'layout_type', 'status',
        'regions_json', 'breakpoints_json', 'metadata',
    ];

    protected function casts(): array
    {
        return ['regions_json' => 'array', 'breakpoints_json' => 'array', 'metadata' => 'array'];
    }
}
