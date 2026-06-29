import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { DocumentItem } from '@/api/types/documents'
import { DocumentActionMenu } from '@/features/documents/components/DocumentActionMenu'
import { DocumentFilterPanel } from '@/features/documents/components/DocumentFilterPanel'
import { DocumentGrid } from '@/features/documents/components/DocumentGrid'
import { DocumentList } from '@/features/documents/components/DocumentList'
import { DocumentRow } from '@/features/documents/components/DocumentRow'
import { DocumentToolbar } from '@/features/documents/components/DocumentToolbar'

const documents: DocumentItem[] = [
  {
    public_id: 'doc-1',
    title: 'Alpha',
    mime_type: 'application/pdf',
    size_bytes: 1024,
    status: 'active',
    updated_at: '2024-01-01',
  },
  {
    public_id: 'doc-2',
    title: 'Beta',
    mime_type: 'text/plain',
    size_bytes: 256,
    status: 'archived',
    updated_at: '2024-02-01',
  },
]

describe('document browse layout components', () => {
  it('renders list table with caption', () => {
    render(<DocumentList documents={documents} onOpen={vi.fn()} />)
    expect(screen.getByTestId('document-list')).toBeInTheDocument()
    expect(screen.getByText('Alpha')).toBeInTheDocument()
  })

  it('renders grid cards', () => {
    render(<DocumentGrid documents={documents} onOpen={vi.fn()} />)
    expect(screen.getByTestId('document-grid')).toBeInTheDocument()
    expect(screen.getByTestId('document-card-doc-2')).toBeInTheDocument()
  })

  it('renders row metadata columns', () => {
    render(
      <table>
        <tbody>
          <DocumentRow document={documents[0]!} onOpen={vi.fn()} />
        </tbody>
      </table>,
    )
    expect(screen.getByText('active')).toBeInTheDocument()
  })
})

describe('document toolbar and filters', () => {
  it('renders toolbar labels and actions', async () => {
    const user = userEvent.setup()
    const onRefresh = vi.fn()

    render(
      <DocumentToolbar
        viewMode="list"
        search=""
        onSearchChange={vi.fn()}
        onViewModeChange={vi.fn()}
        onRefresh={onRefresh}
      />,
    )

    await user.click(screen.getByRole('button', { name: 'Refresh documents' }))
    expect(onRefresh).toHaveBeenCalled()
  })

  it('renders filter panel labels', () => {
    render(
      <DocumentFilterPanel
        filters={[{ filter_key: 'status', label: 'Status', filter_type: 'select' }]}
        onFilterChange={vi.fn()}
      />,
    )
    expect(screen.getByLabelText('Status')).toBeInTheDocument()
  })
})

describe('document action menu', () => {
  it('hides upload when not allowed', () => {
    render(<DocumentActionMenu canUpload={false} onRefresh={vi.fn()} />)
    expect(screen.queryByRole('button', { name: 'Upload document' })).not.toBeInTheDocument()
  })

  it('shows action message', () => {
    render(<DocumentActionMenu onRefresh={vi.fn()} message="Download unavailable" />)
    expect(screen.getByText('Download unavailable')).toBeInTheDocument()
  })
})
