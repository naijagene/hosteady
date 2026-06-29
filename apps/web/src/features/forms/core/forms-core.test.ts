import { describe, expect, it, vi } from 'vitest'
import type { FormDefinition } from '@/api/types/forms'
import {
  buildFormDefaultValues,
  mergeFormDefaultValues,
} from '@/features/forms/core/form-defaults'
import {
  evaluateCondition,
  evaluateConditions,
  resolveTargetVisibility,
} from '@/features/forms/core/form-conditions'
import {
  applyBackendFieldErrors,
  flattenFieldErrors,
  toFormSubmissionError,
} from '@/features/forms/core/form-errors'
import {
  getFieldByKey,
  normalizeFormDefinitionModel,
} from '@/features/forms/core/form-normalizer'
import {
  applyFieldPermissions,
  resolveFieldPermissionState,
} from '@/features/forms/core/form-permissions'
import {
  buildSubmissionMetadata,
  buildSubmissionPayload,
} from '@/features/forms/core/form-transform'
import {
  buildFieldValidationRules,
  buildFormValidationRules,
} from '@/features/forms/core/form-validation'
import { ApiError } from '@/api/errors'

const definition: FormDefinition = {
  module_key: 'platform',
  form_key: 'profile',
  name: 'Profile',
  sections: [
    {
      section_key: 'main',
      label: 'Main',
      sort_order: 1,
      fields: ['name', 'email', 'role'],
    },
  ],
  fields: [
    {
      field_key: 'name',
      label: 'Name',
      field_type: 'text',
      required: true,
      section_key: 'main',
    },
    {
      field_key: 'email',
      label: 'Email',
      field_type: 'email',
      section_key: 'main',
      validation_rules: [{ field: 'email', rule: 'email' }],
    },
    {
      field_key: 'role',
      label: 'Role',
      field_type: 'select',
      section_key: 'main',
      options: [{ value: 'admin', label: 'Admin' }],
      conditions: [
        {
          key: 'show-role',
          field: 'name',
          operator: 'is_not_empty',
          target_key: 'role',
        },
      ],
    },
    {
      field_key: 'secret',
      label: 'Secret',
      field_type: 'hidden',
      default_value: 'hidden-value',
    },
  ],
  conditions: [],
  validation_rules: [{ field: 'name', rule: 'min_length', parameters: { min: 2 } }],
}

describe('form-normalizer', () => {
  it('normalizes sections and field map', () => {
    const model = normalizeFormDefinitionModel(definition)
    expect(model.sections[0].fields).toHaveLength(3)
    expect(getFieldByKey(model, 'name')?.label).toBe('Name')
  })
})

describe('form-defaults', () => {
  it('builds default values by field type', () => {
    const model = normalizeFormDefinitionModel(definition)
    expect(buildFormDefaultValues(model)).toEqual({
      name: '',
      email: '',
      role: '',
      secret: 'hidden-value',
    })
  })

  it('merges initial values', () => {
    const model = normalizeFormDefinitionModel(definition)
    expect(mergeFormDefaultValues(model, { name: 'Ada' }).name).toBe('Ada')
  })
})

describe('form-validation', () => {
  it('builds required validation', () => {
    const model = normalizeFormDefinitionModel(definition)
    const rules = buildFieldValidationRules(model.fields[0])
    expect(rules.required).toBeTruthy()
  })

  it('builds email validation', () => {
    const model = normalizeFormDefinitionModel(definition)
    const rules = buildFieldValidationRules(model.fields[1])
    expect(rules.pattern).toBeTruthy()
  })

  it('builds min_length validation', () => {
    const model = normalizeFormDefinitionModel(definition)
    const allRules = buildFormValidationRules(model.fields, model.validationRules)
    expect(allRules.name.minLength).toBeTruthy()
  })

  it('validates regex rules', () => {
    const model = normalizeFormDefinitionModel(definition)
    const field = {
      ...model.fields[0],
      validation_rules: [{ field: 'name', rule: 'regex', parameters: { pattern: '^Ada' } }],
    }
    const rules = buildFieldValidationRules(field)
    const validator =
      typeof rules.validate === 'object' ? rules.validate?.regex : undefined
    expect(typeof validator).toBe('function')
    if (typeof validator === 'function') {
      expect(validator('Ada', {} as never)).toBe(true)
      expect(validator('Bob', {} as never)).not.toBe(true)
    }
  })

  it('validates numeric rules', () => {
    const model = normalizeFormDefinitionModel(definition)
    const field = {
      ...model.fields[0],
      field_type: 'number',
      validation_rules: [{ field: 'name', rule: 'integer' }],
    }
    const rules = buildFieldValidationRules(field)
    const validator =
      typeof rules.validate === 'object' ? rules.validate?.numeric : undefined
    if (typeof validator === 'function') {
      expect(validator('2', {} as never)).toBe(true)
      expect(validator('2.5', {} as never)).not.toBe(true)
    }
  })
})

describe('form-conditions', () => {
  it('evaluates equals and not equals', () => {
    expect(
      evaluateCondition(
        { key: '1', field: 'status', operator: 'equals', value: 'active' },
        { status: 'active' },
      ),
    ).toBe(true)
    expect(
      evaluateCondition(
        { key: '2', field: 'status', operator: 'not_equals', value: 'active' },
        { status: 'draft' },
      ),
    ).toBe(true)
  })

  it('evaluates contains operators', () => {
    expect(
      evaluateCondition(
        { key: '1', field: 'note', operator: 'contains', value: 'hello' },
        { note: 'say hello world' },
      ),
    ).toBe(true)
    expect(
      evaluateCondition(
        { key: '2', field: 'note', operator: 'not_contains', value: 'bye' },
        { note: 'hello' },
      ),
    ).toBe(true)
  })

  it('evaluates comparison operators', () => {
    expect(
      evaluateCondition(
        { key: '1', field: 'count', operator: 'greater_than', value: 1 },
        { count: 2 },
      ),
    ).toBe(true)
    expect(
      evaluateCondition(
        { key: '2', field: 'count', operator: 'less_than', value: 5 },
        { count: 2 },
      ),
    ).toBe(true)
  })

  it('evaluates empty operators', () => {
    expect(
      evaluateCondition(
        { key: '1', field: 'name', operator: 'is_empty', value: null },
        { name: '' },
      ),
    ).toBe(true)
    expect(
      evaluateCondition(
        { key: '2', field: 'name', operator: 'is_not_empty', value: null },
        { name: 'Ada' },
      ),
    ).toBe(true)
  })

  it('evaluates in operators', () => {
    expect(
      evaluateCondition(
        { key: '1', field: 'role', operator: 'in', value: ['admin', 'user'] },
        { role: 'admin' },
      ),
    ).toBe(true)
    expect(
      evaluateCondition(
        { key: '2', field: 'role', operator: 'not_in', value: ['guest'] },
        { role: 'admin' },
      ),
    ).toBe(true)
  })

  it('resolves target visibility', () => {
    const visible = resolveTargetVisibility(
      definition.fields![2].conditions ?? [],
      { name: 'Ada' },
      'role',
    )
    expect(visible).toBe(true)
  })

  it('evaluates all conditions', () => {
    expect(
      evaluateConditions(
        [{ key: '1', field: 'a', operator: 'equals', value: 1 }],
        { a: 1 },
      ),
    ).toBe(true)
  })
})

describe('form-permissions', () => {
  it('hides field without read permission', () => {
    const model = normalizeFormDefinitionModel(definition)
    const field = {
      ...model.fields[0],
      permissions: { read_permission: 'profile.read' },
    }
    expect(resolveFieldPermissionState(field, []).hidden).toBe(true)
  })

  it('marks field readonly without write permission', () => {
    const model = normalizeFormDefinitionModel(definition)
    const field = {
      ...model.fields[0],
      permissions: { write_permission: 'profile.write' },
    }
    expect(resolveFieldPermissionState(field, []).readonly).toBe(true)
  })

  it('applies permissions to fields', () => {
    const model = normalizeFormDefinitionModel(definition)
    const updated = applyFieldPermissions(
      model.fields.map((field) => ({
        ...field,
        permissions: { read_permission: 'profile.read' },
      })),
      ['profile.read'],
    )
    expect(updated[0].visible).toBe(true)
  })
})

describe('form-transform', () => {
  it('omits hidden fields from payload', () => {
    const model = normalizeFormDefinitionModel(definition)
    const payload = buildSubmissionPayload(
      { name: 'Ada', email: 'ada@example.com', role: 'admin', secret: 'x' },
      model,
      {
        visibleFieldKeys: new Set(['name', 'email', 'secret']),
        preserveHidden: false,
      },
    )
    expect(payload.role).toBeUndefined()
    expect(payload.secret).toBe('x')
  })

  it('preserves hidden fields when configured', () => {
    const model = normalizeFormDefinitionModel(definition)
    const payload = buildSubmissionPayload(
      { name: 'Ada', email: 'ada@example.com', role: 'admin', secret: 'x' },
      model,
      {
        visibleFieldKeys: new Set(['name', 'email']),
        preserveHidden: true,
      },
    )
    expect(payload.role).toBe('admin')
  })

  it('builds submission metadata', () => {
    expect(
      buildSubmissionMetadata({
        moduleKey: 'platform',
        formKey: 'profile',
        source: 'web',
        page: 'home',
      }).source,
    ).toBe('web')
  })
})

describe('form-errors', () => {
  it('maps api validation errors', () => {
    const error = new ApiError('Validation failed', {
      kind: 'validation',
      status: 422,
      body: { message: 'Validation failed', errors: { email: ['Invalid email'] } },
    })
    const mapped = toFormSubmissionError(error)
    expect(mapped.field_errors.email[0]).toBe('Invalid email')
  })

  it('applies backend field errors to form', () => {
    const setError = vi.fn()
    applyBackendFieldErrors(setError, { name: ['Required'] })
    expect(setError).toHaveBeenCalledWith('name', {
      type: 'server',
      message: 'Required',
    })
  })

  it('flattens field errors', () => {
    expect(flattenFieldErrors({ email: ['Invalid'] })).toEqual(['email: Invalid'])
  })
})
