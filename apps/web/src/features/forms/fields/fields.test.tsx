import { describe, expect, it } from 'vitest'
import { createElement } from 'react'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { resolveFieldComponent } from '@/features/forms/fields'
import type { NormalizedFormField } from '@/features/forms/types'
import { useForm } from 'react-hook-form'
import { EmailField } from '@/features/forms/fields/basic-fields'

function FieldHarness({
  field,
}: {
  field: NormalizedFormField
}) {
  const { register, control } = useForm<Record<string, unknown>>({
    defaultValues: { [field.field_key]: field.default_value ?? '' },
  })
  return createElement(resolveFieldComponent(field.field_type), {
    field,
    register,
    control,
  })
}

const baseField = (type: string, overrides?: Partial<NormalizedFormField>): NormalizedFormField => ({
  field_key: 'sample',
  label: 'Sample',
  field_type: type,
  visible: true,
  enabled: true,
  hidden: type === 'hidden',
  readonly: type === 'readonly',
  ...overrides,
})

describe('field renderers', () => {
  const types = [
    'text',
    'textarea',
    'number',
    'email',
    'password',
    'date',
    'datetime',
    'time',
    'select',
    'multiselect',
    'checkbox',
    'radio',
    'switch',
    'boolean',
    'file',
    'document',
    'hidden',
    'readonly',
    'custom_type',
  ]

  types.forEach((type) => {
    it(`renders ${type} field`, () => {
      render(
        <FieldHarness
          field={baseField(type, {
            field_key: type,
            options:
              type === 'select' || type === 'radio'
                ? [{ value: 'a', label: 'A' }]
                : undefined,
          })}
        />,
      )

      if (type === 'custom_type') {
        expect(screen.getByTestId('unsupported-field')).toBeInTheDocument()
      } else if (type === 'hidden') {
        expect(document.querySelector('input[type="hidden"]')).toBeTruthy()
      } else {
        expect(document.querySelector(`[data-field-key="${type}"]`)).toBeTruthy()
      }
    })
  })

  it('exposes aria-invalid when error provided', () => {
    function ErrorField() {
      const { register, control } = useForm<Record<string, unknown>>({
        defaultValues: { email: '' },
      })
      return (
        <EmailField
          field={baseField('email', { field_key: 'email' })}
          register={register}
          control={control}
          error="Invalid"
        />
      )
    }

    render(<ErrorField />)
    expect(screen.getByRole('textbox')).toHaveAttribute('aria-invalid', 'true')
  })

  it('file field shows upload placeholder', () => {
    render(<FieldHarness field={baseField('file', { field_key: 'file' })} />)
    expect(screen.getByText('Upload (placeholder)')).toBeInTheDocument()
  })

  it('document field shows browse placeholder', () => {
    render(<FieldHarness field={baseField('document', { field_key: 'doc' })} />)
    expect(screen.getByText('Browse documents (placeholder)')).toBeInTheDocument()
  })
})

describe('field accessibility', () => {
  it('associates label with input', async () => {
    render(<FieldHarness field={baseField('text', { field_key: 'name', label: 'Full name' })} />)
    await userEvent.click(screen.getByLabelText('Full name'))
    expect(screen.getByLabelText('Full name')).toHaveFocus()
  })
})
