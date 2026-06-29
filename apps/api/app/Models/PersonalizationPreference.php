<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationPreference extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_preferences';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'value_payload' => 'array',
            'value_boolean' => 'bool',
        ];
    }

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'preference_key', 'value_type', 'value_payload', 'value_string', 'value_boolean',
        'value_integer', 'value_decimal', 'metadata',
    ];
}
