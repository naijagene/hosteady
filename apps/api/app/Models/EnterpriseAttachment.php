<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Document\Enums\AttachmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseAttachment extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'enterprise_document_id',
        'document_public_id',
        'organization_id',
        'workspace_id',
        'subject_type',
        'subject_public_id',
        'subject_module_key',
        'subject_entity_key',
        'status',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => AttachmentStatus::class,
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDocument::class, 'enterprise_document_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
