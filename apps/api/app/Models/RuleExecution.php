<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleExecution extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'rule_executions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'rule_definition_id',
        'rule_public_id',
        'trigger_type',
        'status',
        'matched_rules_json',
        'actions_applied_json',
        'warnings_json',
        'violations_json',
        'facts_json',
        'metadata',
        'actor_membership_id'
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matched_rules_json' => 'array',
            'actions_applied_json' => 'array',
            'warnings_json' => 'array',
            'violations_json' => 'array',
            'facts_json' => 'array',
            'metadata' => 'array',
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

    public function ruleDefinition(): BelongsTo
    {
        return $this->belongsTo(RuleDefinition::class);
    }
}
