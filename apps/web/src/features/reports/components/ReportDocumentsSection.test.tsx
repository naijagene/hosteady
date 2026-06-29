import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ReportDocumentsSection } from '@/features/reports/components/ReportDocumentsSection'

describe('ReportDocumentsSection', () => {
  it('renders document references safely', () => {
    render(
      <ReportDocumentsSection
        title="Attachments"
        documents={[{ public_id: 'doc-1', title: 'Policy', document_type: 'pdf' }]}
      />,
    )

    expect(screen.getByLabelText('Attachments')).toBeInTheDocument()
    expect(screen.getByText('Policy')).toBeInTheDocument()
  })

  it('renders empty state when no documents provided', () => {
    render(<ReportDocumentsSection documents={[]} />)
    expect(screen.getByText(/No document references/)).toBeInTheDocument()
  })
})
