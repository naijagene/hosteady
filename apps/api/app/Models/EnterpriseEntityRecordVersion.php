<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseEntityRecordVersion extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'enterprise_entity_record_id',
        'organization_id',
        'workspace_id',
        'module_key',
        'entity_key',
        'record_public_id',
        'version_number',
        'record_data',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'record_data' => 'array',
            'metadata' => 'array',
            'version_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(EnterpriseEntityRecord::class, 'enterprise_entity_record_id');
    }
}
