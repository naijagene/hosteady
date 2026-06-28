<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseEntityRecord extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

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
        'entity_key',
        'record_data',
        'search_text',
        'status',
        'visibility',
        'version',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'record_data' => 'array',
            'metadata' => 'array',
            'version' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EnterpriseEntityRecordVersion::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(EnterpriseEntityRecordActivity::class, 'record_public_id', 'public_id');
    }
}
