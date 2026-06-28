<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Notification\Enums\NotificationScope;
use App\Modules\Sdk\Notification\Enums\NotificationTemplateType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
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
        'module_key',
        'type',
        'template_type',
        'subject',
        'body',
        'channels',
        'variables_json',
        'scope',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'template_type' => NotificationTemplateType::class,
            'scope' => NotificationScope::class,
        ];
    }

    /**
     * @return Attribute<array<int|string, mixed>|null, array<int|string, mixed>|null>
     */
    protected function variables(): Attribute
    {
        return Attribute::make(
            get: fn (): array => isset($this->attributes['variables_json'])
                ? (json_decode($this->attributes['variables_json'], true) ?? [])
                : [],
            set: fn (?array $value): array => [
                'variables_json' => $value !== null ? json_encode($value) : null,
            ],
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
