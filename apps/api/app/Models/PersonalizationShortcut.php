<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationShortcut extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_shortcuts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'metadata' => 'array',
        ];
    }

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'shortcut_key', 'label', 'route', 'target', 'is_active', 'metadata',
    ];
}
