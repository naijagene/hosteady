<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NavigationPersonalization extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'navigation_personalizations';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = array (
  0 => 'public_id',
  1 => 'organization_id',
  2 => 'workspace_id',
  3 => 'membership_id',
  4 => 'navigation_definition_id',
  5 => 'personalization_json',
  6 => 'metadata',
);

    protected function casts(): array
    {
        return [
            'personalization_json' => 'array',
            'metadata' => 'array',
        ];
    }
}
