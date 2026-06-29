import { describe, expect, it } from 'vitest'
import {
  normalizeFormDefinition,
  normalizeFormSubmissionResult,
  normalizeFormBindingContext,
} from '@/api/types/forms'

describe('forms api types', () => {
  it('normalizes camelCase form definition', () => {
    const form = normalizeFormDefinition({
      moduleKey: 'platform',
      formKey: 'profile',
      name: 'Profile',
      fields: [
        {
          key: 'email',
          label: 'Email',
          type: 'email',
          validationRules: [{ field: 'email', rule: 'email' }],
        },
      ],
    })

    expect(form.module_key).toBe('platform')
    expect(form.fields?.[0].field_key).toBe('email')
    expect(form.fields?.[0].validation_rules?.[0].rule).toBe('email')
  })

  it('normalizes submission result', () => {
    const result = normalizeFormSubmissionResult({
      moduleKey: 'platform',
      formKey: 'profile',
      success: true,
      entityPublicId: 'entity-1',
    })

    expect(result.entity_public_id).toBe('entity-1')
  })

  it('normalizes binding context', () => {
    const binding = normalizeFormBindingContext(
      {
        mode: 'edit',
        submitEnabled: true,
        preserveHidden: true,
        successMessage: 'Done',
      },
      'platform',
      'profile',
    )

    expect(binding.mode).toBe('edit')
    expect(binding.preserve_hidden).toBe(true)
    expect(binding.success_message).toBe('Done')
  })
})
