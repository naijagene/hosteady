import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { DocumentItem } from '@/api/types/documents'
import { DocumentCard } from '@/features/documents/components/DocumentCard'
import { DocumentEmptyState } from '@/features/documents/components/DocumentEmptyState'
import { DocumentErrorState } from '@/features/documents/components/DocumentErrorState'
import { DocumentLoadingState } from '@/features/documents/components/DocumentLoadingState'
import { DocumentMetadataPanel } from '@/features/documents/components/DocumentMetadataPanel'
import { DocumentSearchBox } from '@/features/documents/components/DocumentSearchBox'
import { DocumentUploadPanel } from '@/features/documents/components/DocumentUploadPanel'
import { DocumentVersionList } from '@/features/documents/components/DocumentVersionList'
import { DocumentAttachmentList } from '@/features/documents/components/DocumentAttachmentList'
import { DocumentViewToggle } from '@/features/documents/components/DocumentViewToggle'

const document: DocumentItem = {
  public_id: 'doc-1',
  title: 'Policy Document',
  mime_type: 'application/pdf',
  size_bytes: 4096,
  status: 'active',
  updated_at: '2024-01-01',
  tags: [{ tag_key: 'policy', label: 'Policy' }],
}

function renderWithQuery(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>)
}

describe('document state components', () => {
  it('renders loading state with aria-busy', () => {
    render(<DocumentLoadingState />)
    expect(screen.getByTestId('document-loading-state')).toHaveAttribute('aria-busy', 'true')
  })

  it('renders error and empty states', () => {
    render(<DocumentErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
    render(<DocumentEmptyState message="Nothing here" />)
    expect(screen.getByText('Nothing here')).toBeInTheDocument()
  })
})

describe('document browse components', () => {
  it('renders card metadata', () => {
    render(<DocumentCard document={document} onOpen={vi.fn()} />)
    expect(screen.getByText('Policy Document')).toBeInTheDocument()
    expect(screen.getByText('Policy')).toBeInTheDocument()
  })

  it('handles search and view toggle', async () => {
    const user = userEvent.setup()
    const onSearch = vi.fn()
    const onView = vi.fn()

    render(<DocumentSearchBox value="" onChange={onSearch} />)
    await user.type(screen.getByLabelText('Search documents'), 'policy')
    expect(onSearch).toHaveBeenCalled()

    render(<DocumentViewToggle viewMode="list" onChange={onView} />)
    await user.click(screen.getByRole('button', { name: 'Grid view' }))
    expect(onView).toHaveBeenCalledWith('grid')
  })
})

describe('document detail components', () => {
  it('renders metadata panel', () => {
    render(<DocumentMetadataPanel document={document} />)
    expect(screen.getByLabelText('Document metadata')).toBeInTheDocument()
  })

  it('renders version and attachment lists', () => {
    render(
      <DocumentVersionList
        versions={[{ public_id: 'v1', document_public_id: 'doc-1', version_number: 1 }]}
      />,
    )
    expect(screen.getByText(/v1/)).toBeInTheDocument()

    render(
      <DocumentAttachmentList
        attachments={[
          {
            public_id: 'a1',
            document_public_id: 'doc-1',
            subject_type: 'record',
            subject_public_id: 'rec-1',
          },
        ]}
      />,
    )
    expect(screen.getByText('record')).toBeInTheDocument()
  })
})

describe('document upload panel', () => {
  it('shows disabled placeholder', () => {
    renderWithQuery(<DocumentUploadPanel enabled={false} />)
    expect(screen.getByText('Upload endpoint not available.')).toBeInTheDocument()
  })

  it('shows selected file metadata', async () => {
    const user = userEvent.setup()
    renderWithQuery(<DocumentUploadPanel enabled />)
    const input = screen.getByLabelText('Choose file')
    await user.upload(input, new File(['hello'], 'hello.txt', { type: 'text/plain' }))
    expect(screen.getByText(/hello.txt/)).toBeInTheDocument()
  })
})
