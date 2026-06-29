import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useForm } from 'react-hook-form'
import { DocumentField } from '@/features/forms/fields/document-fields'
import type { NormalizedFormField } from '@/features/forms/types'

vi.mock('@/features/documents/components/DocumentPicker', () => ({
  DocumentPicker: ({
    open,
    onConfirm,
    onClose,
  }: {
    open: boolean
    onConfirm: (result: { documents: Array<{ public_id: string; title: string }> }) => void
    onClose: () => void
  }) =>
    open ? (
      <div>
        <button
          type="button"
          onClick={() => onConfirm({ documents: [{ public_id: 'doc-1', title: 'Policy Document' }] })}
        >
          Choose policy
        </button>
        <button type="button" onClick={onClose}>
          Close picker
        </button>
      </div>
    ) : null,
}))

function Harness() {
  const { control, register } = useForm<Record<string, unknown>>({ defaultValues: { doc: '' } })
  const field: NormalizedFormField = {
    field_key: 'doc',
    label: 'Document',
    field_type: 'document',
    visible: true,
    enabled: true,
    hidden: false,
    readonly: false,
  }

  return <DocumentField field={field} register={register} control={control} />
}

describe('DocumentField', () => {
  it('stores selected document public id', async () => {
    const user = userEvent.setup()
    render(<Harness />)

    await user.click(screen.getByRole('button', { name: 'Browse documents' }))
    await user.click(screen.getByText('Choose policy'))

    expect(screen.getByDisplayValue('doc-1')).toBeInTheDocument()
    expect(screen.getByText('Selected: Policy Document')).toBeInTheDocument()
  })
})
