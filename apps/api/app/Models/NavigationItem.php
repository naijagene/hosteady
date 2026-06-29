<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'navigation_items';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = array (
  0 => 'public_id',
  1 => 'organization_id',
  2 => 'workspace_id',
  3 => 'navigation_definition_id',
  4 => 'parent_item_id',
  5 => 'application_id',
  6 => 'module_key',
  7 => 'item_key',
  8 => 'label',
  9 => 'item_type',
  10 => 'route',
  11 => 'icon',
  12 => 'badge_json',
  13 => 'visibility',
  14 => 'conditions_json',
  15 => 'permissions_json',
  16 => 'roles_json',
  17 => 'sort_order',
  18 => 'metadata',
);

    protected function casts(): array
    {
        return [
            'badge_json' => 'array',
            'conditions_json' => 'array',
            'permissions_json' => 'array',
            'roles_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_item_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_item_id')->orderBy('sort_order');
    }
}
