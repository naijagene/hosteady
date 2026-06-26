<?php

namespace App\Models;

use App\Enums\FileCategory;
use App\Enums\FileVisibility;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformFile extends Model
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
        'entity_reference',
        'filename',
        'original_filename',
        'extension',
        'mime_type',
        'category',
        'checksum',
        'size_bytes',
        'visibility',
        'storage_disk',
        'storage_path',
        'uploaded_by_user_id',
        'uploaded_membership_id',
        'display_name',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_reference' => 'array',
            'metadata' => 'array',
            'visibility' => FileVisibility::class,
            'category' => FileCategory::class,
            'size_bytes' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function uploadedMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'uploaded_membership_id');
    }
}
