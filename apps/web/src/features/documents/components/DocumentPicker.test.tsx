import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DocumentPicker } from '@/features/documents/components/DocumentPicker'

vi.mock('@/features/documents/components/DocumentManager', () => ({
  DocumentManager: ({
    onSelectionChange,
  }: {
    onSelectionChange?: (selection: Array<{ public_id: string; title: string }>) => void
  }) => (
    <button
      type="button"
      onClick={() => onSelectionChange?.([{ public_id: 'doc-1', title: 'Policy Document' }])}
    >
      Mock select document
    </button>
  ),
}))

describe('DocumentPicker', () => {
  it('confirms selected documents', async () => {
    const user = userEvent.setup()
    const onConfirm = vi.fn()
    const onClose = vi.fn()

    render(<DocumentPicker open onClose={onClose} onConfirm={onConfirm} />)

    await user.click(screen.getByText('Mock select document'))
    await user.click(screen.getByRole('button', { name: 'Confirm document selection' }))

    expect(onConfirm).toHaveBeenCalledWith({
      documents: [{ public_id: 'doc-1', title: 'Policy Document' }],
      selection_mode: 'single',
    })
  })
})
