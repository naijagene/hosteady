import { beforeEach, describe, expect, it, vi } from 'vitest'
import { act, renderHook } from '@testing-library/react'
import * as documentsApi from '@/api/endpoints/documents'
import { useDocumentSelection } from '@/features/documents/hooks/useDocumentSelection'
import { useDocumentUpload } from '@/features/documents/hooks/useDocumentUpload'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { ReactNode } from 'react'

function wrapper({ children }: { children: ReactNode }) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return <QueryClientProvider client={client}>{children}</QueryClientProvider>
}

describe('useDocumentSelection', () => {
  it('selects and clears documents', () => {
    const { result } = renderHook(() => useDocumentSelection(false))

    act(() => {
      result.current.toggleSelection({ public_id: 'doc-1', title: 'Alpha' })
    })

    expect(result.current.isSelected('doc-1')).toBe(true)

    act(() => {
      result.current.clearSelection()
    })

    expect(result.current.selected).toHaveLength(0)
  })
})

describe('useDocumentUpload', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('uploads document and invalidates query cache', async () => {
    vi.spyOn(documentsApi, 'uploadDocument').mockResolvedValue({
      document: { public_id: 'doc-1', title: 'Uploaded' },
      status: 'completed',
    })

    const { result } = renderHook(() => useDocumentUpload({ enabled: true }), { wrapper })

    await act(async () => {
      await result.current.upload({
        file: new File(['hello'], 'hello.txt', { type: 'text/plain' }),
        title: 'Uploaded',
      })
    })

    expect(result.current.result?.document.title).toBe('Uploaded')
  })

  it('blocks upload when disabled', async () => {
    const { result } = renderHook(() => useDocumentUpload({ enabled: false }), { wrapper })

    await act(async () => {
      await result.current.upload({
        file: new File(['hello'], 'hello.txt', { type: 'text/plain' }),
      })
    })

    expect(result.current.error).toBe('Upload is not enabled.')
  })
})
