<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiPersonalization extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'ui_personalizations';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'membership_id',
        'application_id', 'page_public_id', 'personalization_json', 'metadata',
    ];

    protected function casts(): array
    {
        return ['personalization_json' => 'array', 'metadata' => 'array'];
    }
}
