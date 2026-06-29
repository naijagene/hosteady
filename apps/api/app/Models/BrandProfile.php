<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandProfile extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'brand_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'theme_definition_id', 'name', 'logo_url',
        'colors_json', 'typography_json', 'assets_json', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'colors_json' => 'array',
            'typography_json' => 'array',
            'assets_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ThemeDefinition::class, 'theme_definition_id');
    }
}
