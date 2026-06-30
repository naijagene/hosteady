<?php

namespace Tests\Feature\Services\Rules;

use App\Models\Permission;
use App\Models\Role;
use App\Modules\Sdk\Rules\Contracts\RuleEngine;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Data\RuleExecutionRequest;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Services\Module\ModuleDoctorService;
use App\Services\Rules\EnterpriseRuleEngineService;
use App\Services\Rules\RuleActionExecutorService;
use App\Services\Rules\RuleDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M6EnterpriseBusinessRulesTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_rule_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleStatus $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleStatus::cases());
        foreach (array (
  0 => 'draft',
  1 => 'enabled',
  2 => 'disabled',
  3 => 'archived',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleType $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleType::cases());
        foreach (array (
  0 => 'validation',
  1 => 'calculation',
  2 => 'visibility',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_scope_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleScope $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleScope::cases());
        foreach (array (
  0 => 'organization',
  1 => 'workspace',
  2 => 'module',
  3 => 'entity',
  4 => 'global',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_trigger_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleTriggerType $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleTriggerType::cases());
        foreach (array (
  0 => 'manual',
  1 => 'entity_creating',
  2 => 'form_validating',
  3 => 'form_submitted',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_condition_operator_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleConditionOperator $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleConditionOperator::cases());
        foreach (array (
  0 => 'equals',
  1 => 'not_equals',
  2 => 'contains',
  3 => 'greater_than',
  4 => 'between',
  5 => 'regex',
  6 => 'is_empty',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_action_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleActionType $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleActionType::cases());
        foreach (array (
  0 => 'add_violation',
  1 => 'set_value',
  2 => 'calculate_value',
  3 => 'noop',
  4 => 'send_notification',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_execution_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleExecutionStatus $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleExecutionStatus::cases());
        foreach (array (
  0 => 'pending',
  1 => 'completed',
  2 => 'partial',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_severity_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Rules\Enums\RuleSeverity $case) => $case->value, \App\Modules\Sdk\Rules\Enums\RuleSeverity::cases());
        foreach (array (
  0 => 'info',
  1 => 'warning',
  2 => 'error',
  3 => 'critical',
) as $expected) {
            $this->assertContains($expected, $cases);
        }
    }

    public function test_rule_condition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleCondition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleCondition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_action_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleAction::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleAction::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_fact_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleFact::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleFact::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_context_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleContext::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleContext::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_set_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleSetDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleSetDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_evaluation_request_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_violation_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleViolation::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleViolation::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_trace_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleTrace::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleTrace::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_evaluation_result_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleEvaluationResult::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleEvaluationResult::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_execution_request_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleExecutionRequest::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleExecutionRequest::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_execution_result_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleExecutionResult::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleExecutionResult::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_statistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_rule_health_report_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Rules\Data\RuleHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Rules\Data\RuleHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_condition_operator_equals(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'equals', 'value' => 'active']), ['status' => 'active']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_not_equals(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'not_equals', 'value' => 'inactive']), ['status' => 'active']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_contains(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'contains', 'value' => 'world']), ['status' => 'hello world']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_not_contains(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'not_contains', 'value' => 'xyz']), ['status' => 'hello']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_greater_than(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'greater_than', 'value' => 5]), ['status' => 10]);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_greater_than_or_equal(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'greater_than_or_equal', 'value' => 10]), ['status' => 10]);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_less_than(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'less_than', 'value' => 5]), ['status' => 3]);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_less_than_or_equal(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'less_than_or_equal', 'value' => 5]), ['status' => 5]);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_between(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'between', 'value' => array (
  0 => 1,
  1 => 10,
)]), ['status' => 5]);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_in(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'in', 'value' => array (
  0 => 'a',
  1 => 'b',
  2 => 'c',
)]), ['status' => 'b']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_not_in(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'not_in', 'value' => array (
  0 => 'a',
  1 => 'b',
)]), ['status' => 'z']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_is_empty(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'is_empty', 'value' => NULL]), ['status' => '']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_is_not_empty(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'is_not_empty', 'value' => NULL]), ['status' => 'value']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_starts_with(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'starts_with', 'value' => 'prefix']), ['status' => 'prefix-value']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_ends_with(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'ends_with', 'value' => 'suffix']), ['status' => 'value-suffix']);
        $this->assertTrue($matched);
    }

    public function test_condition_operator_regex(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition::fromArray(['field' => 'status', 'operator' => 'regex', 'value' => '^abc']), ['status' => 'abc123']);
        $this->assertTrue($matched);
    }

    public function test_safe_action_add_violation(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'add_violation', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_set_value(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'set_value', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_calculate_value(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'calculate_value', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_require_field(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'require_field', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_show_field(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'show_field', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_hide_field(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'hide_field', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_safe_action_noop(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000901', 'rule_set_public_id' => 'set', 'name' => 'Test']);
        $result = $executor->execute([['type' => 'noop', 'field' => 'amount', 'value' => 10, 'message' => 'Violation', 'severity' => 'warning']], ['amount' => 1], $rule);
        $this->assertNotEmpty($result['actions_applied']);
    }

    public function test_external_action_send_notification_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'send_notification']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('send_notification', $result['warnings'][0]['action']);
    }

    public function test_external_action_start_workflow_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'start_workflow']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('start_workflow', $result['warnings'][0]['action']);
    }

    public function test_external_action_create_task_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'create_task']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('create_task', $result['warnings'][0]['action']);
    }

    public function test_external_action_approve_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'approve']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('approve', $result['warnings'][0]['action']);
    }

    public function test_external_action_reject_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'reject']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('reject', $result['warnings'][0]['action']);
    }

    public function test_external_action_attach_document_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'attach_document']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('attach_document', $result['warnings'][0]['action']);
    }

    public function test_external_action_tag_record_warns(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'tag_record']], [], $rule);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame('tag_record', $result['warnings'][0]['action']);
    }

    public function test_external_action_emit_event_executes_when_integration_bound(): void
    {
        $executor = app(\App\Services\Rules\RuleActionExecutorService::class);
        $rule = \App\Modules\Sdk\Rules\Data\RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000902', 'rule_set_public_id' => 'set', 'name' => 'External']);
        $result = $executor->execute([['type' => 'emit_event', 'metadata' => ['event_name' => 'rule.test.event']]], [], $rule);
        $this->assertNotEmpty($result['actions_applied']);
        $this->assertSame('emit_event', $result['actions_applied'][0]['type']);
    }

    public function test_rule_set_crud_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);

        $set = $service->createSet($context, RuleSetDefinition::fromArray([
            'name' => 'Validation Rules',
            'scope' => 'organization',
            'status' => 'draft',
        ]));

        $this->assertSame('Validation Rules', $set->name);
        $enabled = $service->enableSet($context, $set->publicId);
        $this->assertSame('enabled', $enabled->status);
    }

    public function test_rule_definition_crud_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Set A', 'status' => 'enabled']));

        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Require status',
            'type' => 'validation',
            'trigger_type' => 'manual',
            'status' => 'enabled',
            'conditions' => [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            'actions' => [['type' => 'add_violation', 'severity' => 'warning', 'message' => 'Inactive']],
        ]));

        $this->assertSame('Require status', $rule->name);
        $shown = $service->showRule($context, $rule->publicId);
        $this->assertSame($rule->publicId, $shown->publicId);
    }

    public function test_evaluate_matching_rule(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Eval Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Match active',
            'status' => 'enabled',
            'trigger_type' => 'manual',
            'conditions' => [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            'actions' => [['type' => 'noop']],
        ]));

        $result = $service->evaluate($context, RuleEvaluationRequest::fromArray([
            'trigger_type' => 'manual',
            'rule_public_ids' => [$rule->publicId],
            'facts' => ['status' => 'active'],
        ]));

        $this->assertTrue($result->matched);
        $this->assertTrue($result->allowed);
    }

    public function test_evaluate_blocks_on_critical_violation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Block Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Critical block',
            'status' => 'enabled',
            'trigger_type' => 'manual',
            'conditions' => [['field' => 'amount', 'operator' => 'greater_than', 'value' => 0]],
            'actions' => [['type' => 'add_violation', 'severity' => 'critical', 'message' => 'Blocked']],
        ]));

        $result = $service->evaluate($context, RuleEvaluationRequest::fromArray([
            'trigger_type' => 'manual',
            'rule_public_ids' => [$rule->publicId],
            'facts' => ['amount' => 10],
        ]));

        $this->assertFalse($result->allowed);
    }

    public function test_execute_records_warnings_for_external_actions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Exec Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'External action',
            'status' => 'enabled',
            'trigger_type' => 'manual',
            'conditions' => [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            'actions' => [['type' => 'send_notification']],
        ]));

        $result = $service->execute($context, RuleExecutionRequest::fromArray([
            'trigger_type' => 'manual',
            'rule_public_ids' => [$rule->publicId],
            'facts' => ['status' => 'active'],
        ]));

        $this->assertNotEmpty($result->warnings);
    }

    public function test_health_report_enabled(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $report = app(RuleDevelopmentService::class)->health($context);
        $this->assertTrue($report->enabled);
    }

    public function test_statistics_after_create(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Stats Set']));
        $stats = $service->statistics($context);
        $this->assertGreaterThanOrEqual(1, $stats->ruleSets);
    }

    public function test_member_can_evaluate_rules(): void
    {
        $owner = $this->tenantContext();
        $member = $this->memberContext($owner);
        app()->instance(TenantContext::class, $member);
        $result = app(RuleDevelopmentService::class)->evaluate($member, RuleEvaluationRequest::fromArray([
            'trigger_type' => 'manual',
            'facts' => ['status' => 'active'],
        ]));
        $this->assertTrue($result->allowed);
    }

    public function test_viewer_cannot_manage_rules(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(RuleDevelopmentService::class)->createSet($viewer, RuleSetDefinition::fromArray(['name' => 'Denied']));
    }

    public function test_module_doctor_includes_business_rules(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('business_rules', $report->platformSummary['enterprise']);
    }

    public function test_permission_catalog_has_rules_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(134, Permission::query()->count());
        foreach (['rules.read', 'rules.manage', 'rules.evaluate', 'rules.execute', 'rules.admin'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_rule_engine_contract_bound(): void
    {
        $this->assertInstanceOf(EnterpriseRuleEngineService::class, app(RuleEngine::class));
    }

    public function test_calculate_value_action(): void
    {
        $executor = app(RuleActionExecutorService::class);
        $rule = RuleDefinition::fromArray(['public_id' => '01900000-0000-7000-8000-000000000903', 'rule_set_public_id' => 'set', 'name' => 'Calc']);
        $result = $executor->execute([
            ['type' => 'calculate_value', 'field' => 'total', 'value' => ['left' => 'a', 'right' => 'b', 'operator' => '+']],
        ], ['a' => 2, 'b' => 3], $rule);
        $this->assertSame(5.0, $result['mutations'][0]['value']);
    }

    public function test_disable_rule_set(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Disable Me', 'status' => 'enabled']));
        $disabled = $service->disableSet($context, $set->publicId);
        $this->assertSame('disabled', $disabled->status);
    }

    public function test_disable_rule_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Rule',
            'status' => 'enabled',
        ]));
        $disabled = $service->disableRule($context, $rule->publicId);
        $this->assertSame('disabled', $disabled->status);
    }

    public function test_list_rules_returns_created_rule(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'List Set', 'status' => 'enabled']));
        $created = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Listed Rule',
            'status' => 'enabled',
        ]));
        $rules = $service->listRules($context);
        $this->assertTrue(collect($rules)->contains(fn ($rule) => $rule->publicId === $created->publicId));
    }

    public function test_evaluations_are_persisted(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $service->evaluate($context, RuleEvaluationRequest::fromArray(['trigger_type' => 'manual', 'facts' => []]));
        $this->assertNotEmpty($service->listEvaluations($context));
    }

    public function test_executions_are_persisted(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $service->execute($context, RuleExecutionRequest::fromArray(['trigger_type' => 'manual', 'facts' => []]));
        $this->assertNotEmpty($service->listExecutions($context));
    }

    public function test_condition_evaluator_or_logic(): void
    {
        $evaluator = app(\App\Services\Rules\RuleConditionEvaluatorService::class);
        $matched = $evaluator->evaluateAll([
            ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'status', 'operator' => 'equals', 'value' => 'pending'],
        ], ['status' => 'pending'], 'or');
        $this->assertTrue($matched);
    }

    public function test_registry_lists_enabled_rules_by_trigger(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Registry Set', 'status' => 'enabled']));
        $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Entity Rule',
            'status' => 'enabled',
            'trigger_type' => 'entity_creating',
        ]));
        $rules = app(\App\Services\Rules\RuleRegistryService::class)->listEnabled(
            $context->organization->id,
            $context->workspace?->id,
            'entity_creating',
        );
        $this->assertNotEmpty($rules);
    }

    public function test_rule_health_runtime_contribution(): void
    {
        $context = $this->tenantContext();
        $contribution = app(\App\Services\Rules\RuleHealthService::class)->runtimeContribution($context);
        $this->assertTrue($contribution['enabled']);
        $this->assertArrayHasKey('rule_sets', $contribution);
    }

    public function test_rule_permission_service_read_for_owner(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->assertTrue(app(\App\Services\Rules\RulePermissionService::class)->canRead($context));
        $this->assertTrue(app(\App\Services\Rules\RulePermissionService::class)->canManage($context));
    }

    public function test_viewer_can_read_but_not_execute(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $permissions = app(\App\Services\Rules\RulePermissionService::class);
        $this->assertTrue($permissions->canRead($viewer));
        $this->assertFalse($permissions->canExecute($viewer));
    }

    public function test_update_rule_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Update Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Original',
            'status' => 'enabled',
        ]));
        $updated = $service->updateRule($context, RuleDefinition::fromArray([
            'public_id' => $rule->publicId,
            'rule_set_public_id' => $set->publicId,
            'name' => 'Updated Name',
            'status' => 'enabled',
        ]));
        $this->assertSame('Updated Name', $updated->name);
    }

    public function test_warning_violation_does_not_block(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(RuleDevelopmentService::class);
        $set = $service->createSet($context, RuleSetDefinition::fromArray(['name' => 'Warn Set', 'status' => 'enabled']));
        $rule = $service->createRule($context, RuleDefinition::fromArray([
            'rule_set_public_id' => $set->publicId,
            'name' => 'Warn only',
            'status' => 'enabled',
            'trigger_type' => 'manual',
            'conditions' => [['field' => 'flag', 'operator' => 'equals', 'value' => 'yes']],
            'actions' => [['type' => 'add_violation', 'severity' => 'warning', 'message' => 'Heads up']],
        ]));
        $result = $service->evaluate($context, RuleEvaluationRequest::fromArray([
            'trigger_type' => 'manual',
            'rule_public_ids' => [$rule->publicId],
            'facts' => ['flag' => 'yes'],
        ]));
        $this->assertTrue($result->allowed);
        $this->assertNotEmpty($result->violations);
    }

    public function test_business_rules_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.business_rules.enabled', true));
    }

    public function test_rule_form_bridge_does_not_throw_without_context(): void
    {
        $bridge = app(\App\Services\Rules\RuleFormBridge::class);
        $issues = [];
        $bridge->validateFormBestEffort(
            \App\Modules\Sdk\Form\Data\FormSubmissionRequest::fromArray(['module_key' => 'demo', 'form_key' => 'demo', 'values' => []]),
            \App\Modules\Sdk\Form\Data\FormDefinition::fromArray(['module_key' => 'demo', 'form_key' => 'demo', 'name' => 'Demo', 'fields' => []]),
            $issues,
        );
        $this->assertSame([], $issues);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-rules-'.uniqid()]);
        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }
}
