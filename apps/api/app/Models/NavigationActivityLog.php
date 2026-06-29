<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationActivityLog extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'navigation_activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = array (
  0 => 'public_id',
  1 => 'organization_id',
  2 => 'workspace_id',
  3 => 'navigation_definition_id',
  4 => 'navigation_item_id',
  5 => 'action',
  6 => 'before_state',
  7 => 'after_state',
  8 => 'actor_user_id',
  9 => 'actor_membership_id',
  10 => 'metadata',
  11 => 'created_at',
);

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');
    }
}
