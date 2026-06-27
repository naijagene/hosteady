<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'module_key',
        'entity_key',
        'name',
        'description',
        'icon',
        'status',
        'visibility',
        'ownership_scope',
        'table_name',
        'class_name',
        'capabilities',
        'fields',
        'relationships',
        'validation_rules',
        'metadata',
        'registered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'visibility' => 'string',
            'ownership_scope' => 'string',
            'capabilities' => 'array',
            'fields' => 'array',
            'relationships' => 'array',
            'validation_rules' => 'array',
            'metadata' => 'array',
            'registered_at' => 'datetime',
        ];
    }

    public function sourceRelationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'source_entity_definition_id');
    }

    public function targetRelationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'target_entity_definition_id');
    }
}
