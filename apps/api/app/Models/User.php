<?php

namespace App\Models;

use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasHeosAudit, HasHeosPublicId, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'display_name',
        'email',
        'password',
        'status',
        'created_by_user_id',
        'updated_by_user_id',
        'deleted_by_user_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }
}
