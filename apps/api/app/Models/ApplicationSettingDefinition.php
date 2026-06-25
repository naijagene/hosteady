<?php

namespace App\Models;

use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceSettingType;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationSettingDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'application_id',
        'setting_key',
        'label',
        'description',
        'setting_type',
        'default_value',
        'is_required',
        'is_sensitive',
        'is_encrypted',
        'scope',
        'category',
        'sort_order',
        'validation_rules',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_value' => 'json',
            'setting_type' => WorkspaceSettingType::class,
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_encrypted' => 'boolean',
            'scope' => SettingDefinitionScope::class,
            'validation_rules' => 'json',
            'status' => SettingDefinitionStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
