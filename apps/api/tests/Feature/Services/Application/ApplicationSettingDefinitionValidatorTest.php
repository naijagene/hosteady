<?php

namespace Tests\Feature\Services\Application;

use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceSettingType;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;
use App\Services\Application\ApplicationSettingDefinitionValidator;
use App\Services\Application\Data\SettingDefinition;
use App\Services\Application\Data\SettingValidationRule;
use Tests\TestCase;

class ApplicationSettingDefinitionValidatorTest extends TestCase
{
    private ApplicationSettingDefinitionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(ApplicationSettingDefinitionValidator::class);
    }

    public function test_accepts_valid_boolean_value(): void
    {
        $definition = $this->definition(WorkspaceSettingType::Boolean);

        $this->assertTrue($this->validator->assertValidValue($definition, true));
    }

    public function test_rejects_invalid_type_for_definition(): void
    {
        $definition = $this->definition(WorkspaceSettingType::Boolean);

        $this->expectException(InvalidWorkspaceSettingTypeException::class);

        $this->validator->assertValidValue($definition, 'not-a-boolean');
    }

    public function test_enforces_string_min_length_rule(): void
    {
        $definition = $this->definition(
            WorkspaceSettingType::String,
            validationRules: new SettingValidationRule(minLength: 8),
        );

        $this->expectException(InvalidWorkspaceSettingTypeException::class);

        $this->validator->assertValidValue($definition, 'short');
    }

    public function test_enforces_string_pattern_rule(): void
    {
        $definition = $this->definition(
            WorkspaceSettingType::String,
            validationRules: new SettingValidationRule(pattern: '^[^@]+@[^@]+\.[^@]+$'),
        );

        $this->expectException(InvalidWorkspaceSettingTypeException::class);

        $this->validator->assertValidValue($definition, 'invalid-email');
    }

    public function test_accepts_value_matching_pattern_rule(): void
    {
        $definition = $this->definition(
            WorkspaceSettingType::String,
            validationRules: new SettingValidationRule(pattern: '^[^@]+@[^@]+\.[^@]+$'),
        );

        $this->assertSame('ops@example.com', $this->validator->assertValidValue($definition, 'ops@example.com'));
    }

    public function test_allows_null_validation_rules(): void
    {
        $definition = $this->definition(WorkspaceSettingType::String);

        $this->assertSame('value', $this->validator->assertValidValue($definition, 'value'));
    }

    private function definition(
        WorkspaceSettingType $type,
        ?SettingValidationRule $validationRules = null,
    ): SettingDefinition {
        return new SettingDefinition(
            publicId: '01999999-9999-7999-8999-999999999999',
            applicationId: '01999999-9999-7999-8999-999999999998',
            settingKey: 'test.key',
            label: 'Test Key',
            description: null,
            settingType: $type,
            defaultValue: null,
            isRequired: false,
            isSensitive: false,
            isEncrypted: false,
            scope: SettingDefinitionScope::Workspace,
            category: null,
            sortOrder: 0,
            validationRules: $validationRules,
            status: SettingDefinitionStatus::Active,
        );
    }
}
