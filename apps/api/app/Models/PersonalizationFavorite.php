<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationFavorite extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_favorites';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'subject_type', 'subject_public_id', 'label', 'metadata',
    ];
}
