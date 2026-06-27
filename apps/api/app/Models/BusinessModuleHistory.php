<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessModuleHistory extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'business_module_history';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'business_module_id',
        'business_module_installation_id',
        'action',
        'before_state',
        'after_state',
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
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function businessModule(): BelongsTo
    {
        return $this->belongsTo(BusinessModule::class);
    }

    public function businessModuleInstallation(): BelongsTo
    {
        return $this->belongsTo(BusinessModuleInstallation::class);
    }
}
