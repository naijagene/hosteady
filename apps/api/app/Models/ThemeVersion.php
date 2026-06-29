<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeVersion extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'theme_versions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'theme_definition_id', 'version_number',
        'status', 'snapshot_json', 'change_summary', 'metadata', 'published_at',
        'published_by_user_id', 'published_by_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ThemeDefinition::class, 'theme_definition_id');
    }
}
