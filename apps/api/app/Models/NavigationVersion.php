<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationVersion extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'navigation_versions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = array (
  0 => 'public_id',
  1 => 'organization_id',
  2 => 'workspace_id',
  3 => 'navigation_definition_id',
  4 => 'version_number',
  5 => 'status',
  6 => 'structure_json',
  7 => 'conditions_json',
  8 => 'change_summary',
  9 => 'metadata',
  10 => 'published_at',
  11 => 'published_by_user_id',
  12 => 'published_by_membership_id',
);

    protected function casts(): array
    {
        return [
            'structure_json' => 'array',
            'conditions_json' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');
    }
}
