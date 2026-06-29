<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'navigation_definitions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = array (
  0 => 'public_id',
  1 => 'organization_id',
  2 => 'workspace_id',
  3 => 'application_id',
  4 => 'module_key',
  5 => 'navigation_key',
  6 => 'name',
  7 => 'description',
  8 => 'type',
  9 => 'status',
  10 => 'visibility',
  11 => 'scope',
  12 => 'current_version_id',
  13 => 'structure_json',
  14 => 'conditions_json',
  15 => 'metadata',
  16 => 'created_by_user_id',
  17 => 'created_membership_id',
);

    protected function casts(): array
    {
        return [
            'structure_json' => 'array',
            'conditions_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(NavigationVersion::class, 'navigation_definition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'navigation_definition_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(NavigationVersion::class, 'current_version_id');
    }
}
