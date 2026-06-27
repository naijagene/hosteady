<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityRelationship extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'source_entity_definition_id',
        'target_entity_definition_id',
        'source_module_key',
        'source_entity_key',
        'target_module_key',
        'target_entity_key',
        'relationship_key',
        'relationship_type',
        'label',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship_type' => 'string',
            'metadata' => 'array',
        ];
    }

    public function sourceEntityDefinition(): BelongsTo
    {
        return $this->belongsTo(EntityDefinition::class, 'source_entity_definition_id');
    }

    public function targetEntityDefinition(): BelongsTo
    {
        return $this->belongsTo(EntityDefinition::class, 'target_entity_definition_id');
    }
}
