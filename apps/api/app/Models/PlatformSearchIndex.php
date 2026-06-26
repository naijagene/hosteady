<?php

namespace App\Models;

use App\Enums\SearchVisibility;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformSearchIndex extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'platform_search_indexes';

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
        'entity_type',
        'entity_public_id',
        'entity_reference',
        'display_name',
        'keywords',
        'metadata',
        'visibility',
        'search_vector',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_reference' => 'array',
            'metadata' => 'array',
            'visibility' => SearchVisibility::class,
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
}
