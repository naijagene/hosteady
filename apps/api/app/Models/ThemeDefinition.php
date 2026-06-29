<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'theme_definitions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key', 'theme_key',
        'name', 'description', 'status', 'scope', 'inheritance_mode', 'parent_theme_id',
        'current_version_id', 'tokens_json', 'metadata', 'created_by_user_id', 'created_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'tokens_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function parentTheme(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_theme_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThemeVersion::class, 'theme_definition_id');
    }

    public function brandProfiles(): HasMany
    {
        return $this->hasMany(BrandProfile::class, 'theme_definition_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ThemeVersion::class, 'current_version_id');
    }
}
