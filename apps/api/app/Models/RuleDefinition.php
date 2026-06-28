<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Sdk\Rules\Enums\RuleScope;
use App\Modules\Sdk\Rules\Enums\RuleStatus;
use App\Modules\Sdk\Rules\Enums\RuleTriggerType;
use App\Modules\Sdk\Rules\Enums\RuleType;

class RuleDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'rule_definitions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'rule_set_id',
        'rule_set_public_id',
        'organization_id',
        'workspace_id',
        'name',
        'description',
        'type',
        'scope',
        'status',
        'trigger_type',
        'priority',
        'conditions_json',
        'actions_json',
        'module_key',
        'entity_key',
        'metadata',
        'deleted_by_user_id'
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conditions_json' => 'array',
            'actions_json' => 'array',
            'metadata' => 'array',
            'scope' => RuleScope::class,
            'status' => RuleStatus::class,
            'type' => RuleType::class,
            'trigger_type' => RuleTriggerType::class,
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

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RuleEvaluation::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(RuleExecution::class);
    }
}
